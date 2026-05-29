<?php
/**
 * gate.php — Exit Popup event receiver
 *
 * POST action=open  { variant, ym_client_id, has_ym, url, referrer, key }
 * POST action=lead  { variant, phone, messenger, ym_client_id, has_ym, url, key, _csrf }
 */

require_once dirname(__DIR__) . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$raw    = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$action = trim($raw['action'] ?? '');
$key    = trim($raw['key']    ?? '');

function gstr(array $src, string $k): string { return trim((string)($src[$k] ?? '')); }
function gateLog(string $level, string $msg, array $ctx = []): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $msg;
    if ($ctx) $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
    @file_put_contents(LOG_DIR . '/gate.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}
function ok(array $data = []): void { echo json_encode(array_merge(['ok' => true], $data)); exit; }
function fail(string $error, int $code = 400): void { http_response_code($code); echo json_encode(['ok' => false, 'error' => $error]); exit; }

if (!$key) { fail('key required'); }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
if (!$site) { gateLog('REJECT', 'site not found', ['key' => $key]); fail('site not found', 404); }
$siteId = (int)$site['id'];

$widgetType = $db->queryOne("SELECT id FROM widget_types WHERE slug = 'exit_popup'");
if (!$widgetType) { fail('widget type not configured', 500); }
$widgetTypeId = (int)$widgetType['id'];

$variant = strtoupper(gstr($raw, 'variant'));
if (!in_array($variant, ['A', 'B', 'C'], true)) { fail('bad variant'); }

$ymId     = gstr($raw, 'ym_client_id');
$hasYm    = (int)(bool)($raw['has_ym'] ?? 0);
$url      = gstr($raw, 'url');
$referrer = gstr($raw, 'referrer');
$ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

// ─── action=open ──────────────────────────────────────────────────────────────
if ($action === 'open') {
    // Deduplicate: same ym_client_id within 1 minute
    if ($ymId) {
        $dup = $db->queryScalar(
            "SELECT 1 FROM popup_events
             WHERE site_id = ? AND action = 'open' AND ym_client_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1",
            [$siteId, $ymId]
        );
        if ($dup) { ok(['dup' => true]); }
    }

    $id = $db->insert('popup_events', [
        'site_id'      => $siteId,
        'action'       => 'open',
        'variant'      => $variant,
        'ym_client_id' => $ymId,
        'has_ym'       => $hasYm,
        'url'          => $url,
        'referrer'     => $referrer,
        'ip'           => $ip,
    ]);
    gateLog('INFO', 'open', ['site_id' => $siteId, 'variant' => $variant]);
    ok(['id' => $id]);
}

// ─── action=lead ──────────────────────────────────────────────────────────────
if ($action === 'lead') {
    // Honeypot
    if (!empty($raw['email'])) { fail('bad request'); }

    // CSRF — 5-min windows with ±25 min tolerance
    $csrfToken = gstr($raw, '_csrf');
    $csrfWin   = (int)floor(time() / 300);
    $csrfValid  = false;
    for ($i = 0; $i <= 5; $i++) {
        if (hash_equals(hash_hmac('sha256', ($csrfWin - $i) . ':' . $key, CSRF_SECRET), $csrfToken)) {
            $csrfValid = true; break;
        }
    }
    if (!$csrfValid) { gateLog('REJECT', 'csrf fail', ['site_id' => $siteId]); fail('invalid token', 403); }

    $phone     = gstr($raw, 'phone');
    $messenger = gstr($raw, 'messenger');
    if (!in_array($messenger, ['tg', 'wa', 'mx', ''], true)) $messenger = '';

    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 12) { fail('invalid phone'); }

    $dupId = (int)$db->queryScalar(
        'SELECT id FROM leads WHERE site_id = ? AND phone = ? ORDER BY id ASC LIMIT 1',
        [$siteId, $cleanPhone]
    );
    if ($dupId) { gateLog('DUP', 'duplicate phone', ['site_id' => $siteId, 'phone' => $cleanPhone, 'prev_id' => $dupId]); }

    $db->beginTransaction();
    try {
        // Save popup event
        $db->insert('popup_events', [
            'site_id'      => $siteId,
            'action'       => 'lead',
            'variant'      => $variant,
            'phone'        => $cleanPhone,
            'messenger'    => $messenger,
            'ym_client_id' => $ymId,
            'has_ym'       => $hasYm,
            'url'          => $url,
            'ip'           => $ip,
        ]);

        // Save unified lead
        $leadId = $db->insert('leads', [
            'site_id'        => $siteId,
            'widget_type_id' => $widgetTypeId,
            'phone'          => $cleanPhone,
            'extra'          => json_encode(['variant' => $variant, 'messenger' => $messenger]),
            'page_url'       => $url,
            'referrer'       => $referrer,
            'utm_source'     => extractUtm($url, 'utm_source'),
            'utm_medium'     => extractUtm($url, 'utm_medium'),
            'utm_campaign'   => extractUtm($url, 'utm_campaign'),
            'utm_content'    => extractUtm($url, 'utm_content'),
            'utm_term'       => extractUtm($url, 'utm_term'),
            'ip'             => $ip,
            'user_agent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ym_client_id'   => $ymId,
            'trigger_type'   => 'exit_intent',
        ]);
        $db->commit();
    } catch (\Throwable $e) {
        $db->rollback();
        gateLog('ERROR', $e->getMessage());
        fail('server error', 500);
    }

    // Dispatch integrations
    dispatchIntegrations($db, $leadId, $siteId, $cleanPhone, $messenger, $url, $ymId, $ip, 'Exit Popup');

    gateLog('OK', 'lead saved', ['site_id' => $siteId, 'variant' => $variant]);
    ok();
}

fail('unknown action');

// ─── Helpers ─────────────────────────────────────────────────────────────────

function extractUtm(string $url, string $param): string {
    if (!$url) return '';
    parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $qs);
    return trim($qs[$param] ?? '');
}

function dispatchIntegrations(Database $db, int $leadId, int $siteId, string $phone, string $messenger, string $url, string $ymId, string $ip, string $source): void {
    $integrations = $db->query(
        "SELECT type, config FROM site_integrations WHERE site_id = ? AND is_active = 1",
        [$siteId]
    );
    foreach ($integrations as $integ) {
        $cfg = json_decode($integ['config'] ?? '{}', true) ?: [];
        if ($integ['type'] === 'telegram') {
            $token  = trim($cfg['tg_token']   ?? '');
            $chatId = trim($cfg['tg_chat_id'] ?? '');
            if ($token && $chatId) sendTelegram($token, $chatId, $phone, $messenger, $url, $ymId, $ip, $source);
        } elseif ($integ['type'] === 'bitrix24') {
            $webhook = trim($cfg['b24_webhook'] ?? '');
            if ($webhook) {
                $b24Id = sendBitrix24($webhook, $phone, $messenger, $url, $ymId, $ip, $cfg['b24_custom_fields'] ?? [], $source);
                if ($b24Id) {
                    $extra = json_decode($db->queryScalar('SELECT extra FROM leads WHERE id = ?', [$leadId]) ?: '{}', true) ?: [];
                    $extra['b24_lead_id'] = $b24Id;
                    $db->execute('UPDATE leads SET extra = ? WHERE id = ?', [json_encode($extra, JSON_UNESCAPED_UNICODE), $leadId]);
                    gateLog('INFO', 'b24 lead saved', ['lead_id' => $leadId, 'b24_id' => $b24Id]);
                }
            }
        }
    }
}

function sendTelegram(string $token, string $chatId, string $phone, string $messenger, string $url, string $ymId, string $ip, string $source): void {
    $msgrMap = ['tg' => 'Telegram', 'wa' => 'WhatsApp', 'mx' => 'Max'];
    $lines   = ["🚨 <b>Новая заявка! [{$source}]</b>", "📱 <b>Телефон:</b> " . htmlspecialchars($phone)];
    if ($messenger) $lines[] = "💬 <b>Мессенджер:</b> " . htmlspecialchars($msgrMap[$messenger] ?? $messenger);
    if ($url)       $lines[] = "📄 <b>Страница:</b> "   . htmlspecialchars($url);
    $utm = extractUtm($url, 'utm_source');
    if ($utm)       $lines[] = "🔗 <b>Источник:</b> "   . htmlspecialchars($utm);
    if ($ymId)      $lines[] = "🔑 <b>YM:</b> "         . htmlspecialchars($ymId);
    $lines[] = "🕐 " . date('d.m.Y H:i:s');
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendMessage', CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => implode("\n", $lines), 'parse_mode' => 'HTML']]);
    curl_exec($ch); curl_close($ch);
}

function sendBitrix24(string $webhook, string $phone, string $messenger, string $url, string $ymId, string $ip, array $customFields, string $source): int {
    $macros = ['{{phone}}' => $phone, '{{page_url}}' => $url, '{{ym_client_id}}' => $ymId, '{{messenger}}' => $messenger, '{{ip}}' => $ip, '{{utm_source}}' => extractUtm($url,'utm_source'), '{{utm_medium}}' => extractUtm($url,'utm_medium'), '{{utm_campaign}}' => extractUtm($url,'utm_campaign'), '{{utm_content}}' => extractUtm($url,'utm_content'), '{{utm_term}}' => extractUtm($url,'utm_term')];
    foreach ($customFields as $k => $v) { $customFields[$k] = str_replace(array_keys($macros), array_values($macros), $v); }
    $fields = array_merge(['TITLE' => "{$source} | " . parse_url($url, PHP_URL_HOST), 'PHONE' => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']], 'SOURCE_ID' => 'WEBFORM', 'COMMENTS' => "URL: {$url}\nIP: {$ip}"], $customFields);
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => rtrim($webhook,'/') . '/crm.lead.add.json', CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_POSTFIELDS => http_build_query(['fields' => $fields])]);
    $resp = curl_exec($ch); curl_close($ch);
    $data = json_decode((string)$resp, true);
    return (int)($data['result'] ?? 0);
}
