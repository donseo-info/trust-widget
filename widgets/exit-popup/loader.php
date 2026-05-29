<?php
/**
 * Exit Popup loader — serves popup.js with injected config.
 * Usage: <script src="/widgets/exit-popup/loader.php?key=API_KEY"
 *                data-gate="https://host/api/gate.php" async></script>
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');

$key = trim($_GET['key'] ?? '');
if (!$key) { echo '/* missing key */'; exit; }

$db   = Database::getInstance();
$site = $db->queryOne('SELECT id FROM sites WHERE api_key = ? AND is_active = 1', [$key]);
if (!$site) { echo '/* site not found */'; exit; }

$widgetType = $db->queryOne("SELECT id FROM widget_types WHERE slug = 'exit_popup'");
if (!$widgetType) { echo '/* widget type not found */'; exit; }

$raw    = $db->queryOne(
    'SELECT config FROM site_widgets WHERE site_id = ? AND widget_type_id = ? AND is_active = 1',
    [(int)$site['id'], (int)$widgetType['id']]
);
$config = $raw ? (json_decode($raw['config'], true) ?: []) : [];

$proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = $proto . '://' . $host;
$gateUrl  = $base . '/api/gate.php';

// Per-variant configs for popup scripts
$variantsCfg = $config['variants'] ?? [];
$enabled     = $config['variants_enabled'] ?? ['A', 'B', 'C'];
$enabled     = array_values(array_intersect($enabled, ['A', 'B', 'C']));
if (!$enabled) $enabled = ['A', 'B', 'C'];

// popup.js reads window._EI (gate/key/counter) and window._EI_variants (enabled set).
// CSRF + YM goals + counter are injected per-variant by api/popup.php.
$ei = json_encode([
    'gate'    => $gateUrl,
    'key'     => $key,
    'counter' => '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

echo "window._EI = {$ei};\n";
echo "window._EI_variants = " . json_encode($enabled) . ";\n";
readfile(__DIR__ . '/popup.js');
