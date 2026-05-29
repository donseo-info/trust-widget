<?php
/**
 * popup.php — serves a configured exit-popup variant as JS.
 *
 * Requested by popup.js:  baseUrl + '/api/popup.php?variant=A&key=API_KEY'
 *
 * Reads the exit_popup config from site_widgets (our schema), merges it with
 * generator defaults, then emits generatePopup{A|B|C}($cfg) (which includes the
 * phone mask pMask()). Also injects CSRF token, YM goals and counter id that
 * popup.js reads from window._EI_csrf / window._EI_ym / window._EI_counter.
 */

require_once dirname(dirname(dirname(__DIR__))) . '/config.php';
require_once dirname(__DIR__) . '/generator.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$variant = strtoupper(trim($_GET['variant'] ?? ''));
if (!in_array($variant, ['A', 'B', 'C'], true)) { http_response_code(400); echo '// bad variant'; exit; }

$key = trim($_GET['key'] ?? '');
if (!$key) { echo '// missing key'; exit; }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
if (!$site) { echo '// site not found'; exit; }
$siteId = (int)$site['id'];

$widgetType = $db->queryOne("SELECT id FROM widget_types WHERE slug = 'exit_popup'");
if (!$widgetType) { echo '// widget type not found'; exit; }

$raw    = $db->queryOne(
    'SELECT config FROM site_widgets WHERE site_id = ? AND widget_type_id = ? AND is_active = 1',
    [$siteId, (int)$widgetType['id']]
);
$config = $raw ? (json_decode($raw['config'], true) ?: []) : [];

// Enabled variants
$enabled = $config['variants_enabled'] ?? ['A', 'B', 'C'];
if (!in_array($variant, (array)$enabled, true)) { echo '// disabled'; exit; }

// Merge admin config for this variant onto generator defaults.
$defaults = popupDefaults($variant);
$saved    = $config['variants'][$variant] ?? [];

// Map our admin keys → generator keys (keeps original generator untouched).
$mapped = [];
if (isset($saved['headline']))  $mapped['headline'] = $saved['headline'];
if (isset($saved['subtext']))   $mapped['subtext']  = $saved['subtext'];
if (!empty($saved['btn_label'])) $mapped['btn']      = $saved['btn_label'];
if (!empty($saved['color_bg']))  $mapped['color']    = $saved['color_bg'];
if (!empty($saved['timer']))     $mapped['timer']    = (int)$saved['timer'];

$cfg = array_merge($defaults, $mapped);

// ─── YM goals + counter (from yandex_metrika integration) ──────────────────────
$ymRow = $db->queryOne(
    "SELECT config FROM site_integrations WHERE site_id = ? AND type = 'yandex_metrika' AND is_active = 1",
    [$siteId]
);
$goalOpen = $goalLead = $counterId = '';
if ($ymRow) {
    $ymCfg     = json_decode($ymRow['config'], true) ?: [];
    $goalOpen  = trim($ymCfg['goal_open']  ?? '');
    $goalLead  = trim($ymCfg['goal_lead']  ?? '');
    $counterId = preg_replace('/[^0-9]/', '', $ymCfg['counter_id'] ?? '');
}

// ─── CSRF token (validated by gate.php) ────────────────────────────────────────
$csrfWindow = (int)floor(time() / 300);
$csrfToken  = hash_hmac('sha256', $csrfWindow . ':' . $key, CSRF_SECRET);

echo "window._EI_csrf='" . $csrfToken . "';\n";
if ($goalOpen !== '' || $goalLead !== '') {
    echo 'window._EI_ym=' . json_encode(['goal_open' => $goalOpen, 'goal_lead' => $goalLead], JSON_UNESCAPED_UNICODE) . ";\n";
}
if ($counterId !== '') {
    echo "window._EI_counter='" . $counterId . "';\n";
}

$fn = 'generatePopup' . $variant;
echo $fn($cfg);
