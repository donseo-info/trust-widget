<?php
/**
 * Callback widget loader — serves widget.js with injected config.
 * Usage: <script src="/widgets/callback/loader.php?key=API_KEY" async></script>
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');

$key  = trim($_GET['key'] ?? '');
if (!$key) { echo '/* missing key */'; exit; }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
if (!$site) { echo '/* site not found */'; exit; }

$widgetType = $db->queryOne("SELECT id FROM widget_types WHERE slug = 'callback'");
if (!$widgetType) { echo '/* widget type not found */'; exit; }

$raw = $db->queryOne(
    'SELECT config FROM site_widgets WHERE site_id = ? AND widget_type_id = ? AND is_active = 1',
    [(int)$site['id'], (int)$widgetType['id']]
);
$config = $raw ? (json_decode($raw['config'], true) ?: []) : [];

// Yandex.Metrika: counter + ОТДЕЛЬНАЯ цель звонка (goal_callback). Дедуп — в widget.js (cookie scw_ym_goal).
$ymRow     = $db->queryOne(
    "SELECT config FROM site_integrations WHERE site_id = ? AND type = 'yandex_metrika' AND is_active = 1",
    [(int)$site['id']]
);
$ymCounter = 0;
$ymGoal    = 'callback_widget';
if ($ymRow) {
    $ymCfg     = json_decode($ymRow['config'], true) ?: [];
    $ymCounter = (int)preg_replace('/[^0-9]/', '', $ymCfg['counter_id'] ?? '');
    if (!empty($ymCfg['goal_callback'])) $ymGoal = trim($ymCfg['goal_callback']);
}

$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base    = $proto . '://' . $host;
$submitUrl = $base . '/api/submit.php';

// CSRF token for this 5-min window
$csrfWin   = (int)floor(time() / 300);
$csrfToken = hash_hmac('sha256', $csrfWin . ':' . $key, CSRF_SECRET);

$cfg = json_encode([
    'submitUrl'        => $submitUrl,
    'siteKey'          => $key,
    'csrfToken'        => $csrfToken,
    'ymCounterId'      => $ymCounter,
    'ymGoal'           => $ymGoal,
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
readfile(__DIR__ . '/widget.js');
