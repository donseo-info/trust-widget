<?php
/**
 * submit.php — Callback Widget lead submission
 * POST JSON body: site_key, phone, page_url, referrer, trigger_type, ym_client_id, utm_*, _csrf, email (honeypot)
 */

require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'error' => 'Method not allowed']); exit; }

$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (!is_array($body)) $body = [];

function ok(int $id): void { echo json_encode(['success' => true, 'lead_id' => $id]); exit; }
function fail(string $error, int $code = 400): void { http_response_code($code); echo json_encode(['success' => false, 'error' => $error]); exit; }
function subLog(string $msg): void { @file_put_contents(LOG_DIR . '/submit.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX); }
function gstr(array $src, string $k): string { return trim((string)($src[$k] ?? '')); }

// Honeypot
if (!empty($body['email'])) { fail('bad request'); }

$siteKey     = gstr($body, 'site_key');
$phone       = gstr($body, 'phone');
$pageUrl     = gstr($body, 'page_url');
$referrer    = gstr($body, 'referrer');
$triggerType = gstr($body, 'trigger_type') ?: 'manual';
$ymId        = gstr($body, 'ym_client_id');
$utmSource   = gstr($body, 'utm_source');
$utmMedium   = gstr($body, 'utm_medium');
$utmCampaign = gstr($body, 'utm_campaign');
$utmContent  = gstr($body, 'utm_content');
$utmTerm     = gstr($body, 'utm_term');

if (!$siteKey || !$phone) { fail('site_key and phone required'); }

$phoneDigits = preg_replace('/\D/', '', $phone);
if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 12) { fail('invalid phone', 422); }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id, name, domain FROM sites WHERE api_key = ? AND is_active = 1', [$siteKey]);
if (!$site) { fail('site not found', 404); }
$siteId = (int)$site['id'];

// CSRF — 5-min windows ±10 min
$csrfToken = gstr($body, '_csrf');
$csrfWin   = (int)floor(time() / 300);
$csrfValid  = false;
for ($i = 0; $i <= 2; $i++) {
    if (hash_equals(hash_hmac('sha256', ($csrfWin - $i) . ':' . $siteKey, CSRF_SECRET), $csrfToken)) {
        $csrfValid = true; break;
    }
}
if (!$csrfValid) { subLog('[CSRF FAIL] site=' . $siteKey . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '')); fail('invalid token', 403); }

$widgetType = $db->queryOne("SELECT id FROM widget_types WHERE slug = 'callback'");
if (!$widgetType) { fail('widget type not found', 500); }
$widgetTypeId = (int)$widgetType['id'];

$dupId = (int)$db->queryScalar(
    'SELECT id FROM leads WHERE site_id = ? AND phone = ? ORDER BY id ASC LIMIT 1',
    [$siteId, $phoneDigits]
);
if ($dupId) { subLog('[DUP] phone=' . $phoneDigits . ' site=' . $siteKey . ' prev_id=' . $dupId); }

$leadId = $db->insert('leads', [
    'site_id'        => $siteId,
    'widget_type_id' => $widgetTypeId,
    'phone'          => $phoneDigits,
    'extra'          => json_encode(['trigger_type' => $triggerType]),
    'page_url'       => $pageUrl,
    'referrer'       => $referrer,
    'utm_source'     => $utmSource,
    'utm_medium'     => $utmMedium,
    'utm_campaign'   => $utmCampaign,
    'utm_content'    => $utmContent,
    'utm_term'       => $utmTerm,
    'ip'             => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'ym_client_id'   => $ymId,
    'trigger_type'   => $triggerType,
]);

subLog('[LEAD] id=' . $leadId . ' site=' . $siteKey . ' phone=' . $phoneDigits . ' trigger=' . $triggerType);

// Integrations
$integrations = $db->query(
    'SELECT type, config FROM site_integrations WHERE site_id = ? AND is_active = 1',
    [$siteId]
);
foreach ($integrations as $integ) {
    $cfg = json_decode($integ['config'] ?? '{}', true) ?: [];
    if ($integ['type'] === 'telegram') {
        $token  = trim($cfg['tg_token']   ?? '');
        $chatId = trim($cfg['tg_chat_id'] ?? '');
        if ($token && $chatId) sendTelegram($token, $chatId, $phoneDigits, $triggerType, $pageUrl, $utmSource, $utmMedium, $utmCampaign, $site['name']);
    } elseif ($integ['type'] === 'bitrix24') {
        $webhook = trim($cfg['b24_webhook'] ?? '');
        if ($webhook) {
            $b24Id = sendBitrix24($webhook, $phoneDigits, $triggerType, $pageUrl, $utmSource, $utmMedium, $utmCampaign, $site['domain'] ?: $site['name'], $_SERVER['REMOTE_ADDR'] ?? '', $cfg['b24_custom_fields'] ?? [], $ymId, $referrer, $utmContent, $utmTerm);
            if ($b24Id) {
                $extra = json_decode($db->queryScalar('SELECT extra FROM leads WHERE id = ?', [$leadId]) ?: '{}', true) ?: [];
                $extra['b24_lead_id'] = $b24Id;
                $db->execute('UPDATE leads SET extra = ? WHERE id = ?', [json_encode($extra, JSON_UNESCAPED_UNICODE), $leadId]);
                subLog('[B24] lead_id=' . $leadId . ' b24_id=' . $b24Id);
            }
        }
    }
}

ok($leadId);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function sendTelegram(string $token, string $chatId, string $phone, string $trigger, string $url, string $src, string $med, string $camp, string $siteName): void {
    $lines = ["📞 <b>Новая заявка! [Обратный звонок]</b>", "📱 <b>Телефон:</b> " . htmlspecialchars($phone), "🌐 <b>Сайт:</b> " . htmlspecialchars($siteName)];
    if ($url)   $lines[] = "📄 <b>Страница:</b> " . htmlspecialchars($url);
    if ($src)   $lines[] = "🔗 <b>Источник:</b> " . htmlspecialchars($src) . ($med ? ' / ' . htmlspecialchars($med) : '');
    if ($camp)  $lines[] = "📊 <b>Кампания:</b> " . htmlspecialchars($camp);
    $lines[] = "🎯 <b>Тип:</b> " . ($trigger === 'auto' ? 'Авто-открытие' : 'Ручной');
    $lines[] = "🕐 " . date('d.m.Y H:i:s');
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendMessage', CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => implode("\n", $lines), 'parse_mode' => 'HTML']]);
    curl_exec($ch); curl_close($ch);
}

function sendBitrix24(string $webhook, string $phone, string $trigger, string $url, string $src, string $med, string $camp, string $title, string $ip, array $cf, string $ymId, string $referrer, string $utmContent, string $utmTerm): int {
    $macros = ['{{phone}}' => $phone, '{{page_url}}' => $url, '{{referrer}}' => $referrer, '{{ym_client_id}}' => $ymId, '{{trigger_type}}' => $trigger, '{{ip}}' => $ip, '{{utm_source}}' => $src, '{{utm_medium}}' => $med, '{{utm_campaign}}' => $camp, '{{utm_content}}' => $utmContent, '{{utm_term}}' => $utmTerm];
    foreach ($cf as $k => $v) $cf[$k] = str_replace(array_keys($macros), array_values($macros), $v);
    $fields = array_merge(['TITLE' => 'Обратный звонок | ' . $title, 'PHONE' => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']], 'SOURCE_ID' => 'WEBFORM', 'COMMENTS' => "URL: {$url}\nТип: {$trigger}\nIP: {$ip}"], $cf);
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => rtrim($webhook,'/') . '/crm.lead.add.json', CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_POSTFIELDS => http_build_query(['fields' => $fields])]);
    $resp = curl_exec($ch); curl_close($ch);
    $data = json_decode((string)$resp, true);
    return (int)($data['result'] ?? 0);
}
