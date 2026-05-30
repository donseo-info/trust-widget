<?php
/**
 * Unified widget loader — один тег для всех виджетов сайта.
 * Отдаёт: core.js + только активные виджеты с их конфигом.
 *
 * Usage: <script src="/widgets/loader.php?key=API_KEY" async></script>
 */

require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');

$key = trim($_GET['key'] ?? '');
if (!$key) { echo '/* missing key */'; exit; }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id, domain, debug_mode FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
if (!$site) { echo '/* site not found */'; exit; }

$siteId = (int)$site['id'];

$proto         = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host          = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base          = $proto . '://' . $host . APP_BASE;
$requestDomain = strtolower(preg_replace('/:\d+$/', '', $host)); // strip port
$allowedDomain = strtolower(trim($site['domain']));

// ─── Domain check ─────────────────────────────────────────────────────────────
$domainAllowed = ($requestDomain === $allowedDomain)
    || ($requestDomain === 'www.' . $allowedDomain)
    || ('www.' . $requestDomain === $allowedDomain)
    || in_array($requestDomain, ['localhost', '127.0.0.1'], true); // dev bypass

if (!$domainAllowed) {
    $db->execute(
        'INSERT INTO loader_attempts (api_key, site_id, request_domain, allowed_domain, ip, user_agent) VALUES (?,?,?,?,?,?)',
        [$key, $siteId, $requestDomain, $allowedDomain,
         $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
         $_SERVER['HTTP_USER_AGENT'] ?? '']
    );
    echo '/* domain not allowed */';
    exit;
}

// Активные виджеты сайта
$activeWidgets = $db->query(
    'SELECT wt.slug, sw.config FROM site_widgets sw
     JOIN widget_types wt ON wt.id = sw.widget_type_id
     WHERE sw.site_id = ? AND sw.is_active = 1',
    [$siteId]
);

if (!$activeWidgets) { echo '/* no active widgets */'; exit; }

// YM интеграция (общая для всех виджетов)
$ymRow = $db->queryOne(
    "SELECT config FROM site_integrations WHERE site_id = ? AND type = 'yandex_metrika' AND is_active = 1",
    [$siteId]
);
$ymCfg     = $ymRow ? (json_decode($ymRow['config'], true) ?: []) : [];
$ymCounter = (int)preg_replace('/[^0-9]/', '', $ymCfg['counter_id'] ?? '');

// CSRF токен (5-минутное окно)
$csrfWin   = (int)floor(time() / 300);
$csrfToken = hash_hmac('sha256', $csrfWin . ':' . $key, CSRF_SECRET);

// ─── 1. Debug flag + core.js ─────────────────────────────────────────────────
$debug = (bool)($site['debug_mode'] ?? false);
echo 'window.__TW_DEBUG = ' . ($debug ? 'true' : 'false') . ";\n";
readfile(__DIR__ . '/core.js');
echo "\n";

// ─── 2. Виджеты ──────────────────────────────────────────────────────────────
foreach ($activeWidgets as $widget) {
    $slug   = $widget['slug'];
    $config = json_decode($widget['config'] ?? '{}', true) ?: [];

    switch ($slug) {

        case 'callback':
            $cfg = json_encode([
                'submitUrl'        => $base . '/api/submit.php',
                'siteKey'          => $key,
                'csrfToken'        => $csrfToken,
                'ymCounterId'      => $ymCounter,
                'ymGoal'           => trim($ymCfg['goal_callback'] ?? 'callback_widget'),
                'buttonColor'      => $config['button_color']       ?? '#25c16f',
                'pulseColor'       => 'rgba(' . implode(',', sscanf($config['button_color'] ?? '#25c16f', '#%02x%02x%02x') ?: [37,193,111]) . ',.35)',
                'position'         => $config['position']           ?? 'right',
                'bottomOffset'     => ($config['bottom_offset'] ?? 30) . 'px',
                'sideOffset'       => ($config['side_offset']   ?? 30) . 'px',
                'title'            => $config['title']              ?? 'Перезвоним за 30 секунд',
                'subtitle'         => $config['subtitle']           ?? 'Оставьте номер — мы сами позвоним',
                'successText'      => $config['success_text']       ?? 'Спасибо! Перезвоним в течение 30 секунд.',
                'submitBtnText'    => $config['submit_btn_text']    ?? 'Перезвоните мне',
                'badgeText'        => $config['badge_text']         ?? 'Перезвонить?',
                'privacyUrl'       => $config['privacy_url']        ?? '',
                'privacyText'      => $config['privacy_text']       ?? 'Политика конфиденциальности',
                'showDelay'        => (int)($config['show_delay']   ?? 5),
                'autoOpen'         => (bool)($config['auto_open']   ?? true),
                'autoOpenScroll'   => (float)($config['auto_open_scroll'] ?? 0.75),
                'autoOpenTime'     => (int)($config['auto_open_time']     ?? 30),
                'autoOpenTitle'    => $config['auto_open_title']    ?? 'Остались вопросы?',
                'autoOpenSubtitle' => $config['auto_open_subtitle'] ?? 'Наш специалист проконсультирует вас бесплатно',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            echo "window.__SCW_CONFIG = {$cfg};\n";
            readfile(__DIR__ . '/callback/widget.js');
            echo "\n";
            break;

        case 'exit_popup':
            $enabled = $config['variants_enabled'] ?? ['A', 'B', 'C'];
            $enabled = array_values(array_intersect((array)$enabled, ['A', 'B', 'C']));
            if (!$enabled) $enabled = ['A', 'B', 'C'];

            $ei = json_encode([
                'gate'      => $base . '/api/gate.php',
                'key'       => $key,
                'counter'   => (string)$ymCounter,
                'popupBase' => $base . '/widgets/exit-popup',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            echo "window._EI = {$ei};\n";
            echo 'window._EI_variants = ' . json_encode($enabled) . ";\n";
            echo "window._EI_csrf = '" . $csrfToken . "';\n";
            if (!empty($ymCfg['goal_open']) || !empty($ymCfg['goal_lead'])) {
                echo 'window._EI_ym = ' . json_encode([
                    'goal_open' => trim($ymCfg['goal_open'] ?? ''),
                    'goal_lead' => trim($ymCfg['goal_lead'] ?? ''),
                ]) . ";\n";
            }
            if ($ymCounter) {
                echo "window._EI_counter = '{$ymCounter}';\n";
            }
            readfile(__DIR__ . '/exit-popup/popup.js');
            echo "\n";
            break;
    }
}
