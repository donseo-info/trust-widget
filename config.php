<?php
/**
 * Trust Widget Platform — Configuration
 * Copy to config.local.php and set real credentials there.
 */

define('TW_ROOT', dirname(__FILE__));
define('TW_VERSION', '1.0.0');

// Base URL path — вычисляется от DOCUMENT_ROOT, не зависит от текущего скрипта
define('APP_BASE', rtrim(str_replace(
    rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'),
    '',
    TW_ROOT
), '/\\'));

/** Build URL with base path prefix. */
function url(string $path = ''): string {
    return APP_BASE . '/' . ltrim($path, '/');
}

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    $_ENV['TW_DB_HOST']    ?? 'localhost');
define('DB_PORT',    $_ENV['TW_DB_PORT']    ?? '3306');
define('DB_NAME',    $_ENV['TW_DB_NAME']    ?? 'susilknv_trust');
define('DB_USER',    $_ENV['TW_DB_USER']    ?? 'susilknv_trust');
define('DB_PASS',    $_ENV['TW_DB_PASS']    ?? 'LfYh&7hAzZ*y');
define('DB_CHARSET', 'utf8mb4');

// ─── Security ────────────────────────────────────────────────────────────────
define('CSRF_SECRET',   $_ENV['TW_CSRF_SECRET']   ?? 'change-me-in-production-32chars');
define('SESSION_NAME',  'tw_sess');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// ─── App ─────────────────────────────────────────────────────────────────────
define('APP_DEBUG',  (bool)($_ENV['TW_DEBUG'] ?? false));
define('LOG_DIR',    TW_ROOT . '/logs');

// Load local overrides (not committed to git)
$_local = TW_ROOT . '/config.local.php';
if (file_exists($_local)) {
    require_once $_local;
}
unset($_local);

// ─── Autoloader ──────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        'Database'                => TW_ROOT . '/core/Database.php',
        'InstallCode'             => TW_ROOT . '/core/InstallCode.php',
        'Router'                  => TW_ROOT . '/core/Router.php',
        'Controller'              => TW_ROOT . '/core/Controller.php',
        'Model'                   => TW_ROOT . '/core/Model.php',
        'Auth'                    => TW_ROOT . '/core/Auth.php',
        'View'                    => TW_ROOT . '/core/View.php',
        'SiteModel'               => TW_ROOT . '/models/SiteModel.php',
        'LeadModel'               => TW_ROOT . '/models/LeadModel.php',
        'IntegrationModel'        => TW_ROOT . '/models/IntegrationModel.php',
        'WidgetTypeModel'         => TW_ROOT . '/models/WidgetTypeModel.php',
        'SiteWidgetModel'         => TW_ROOT . '/models/SiteWidgetModel.php',
        'PopupEventModel'         => TW_ROOT . '/models/PopupEventModel.php',
        'UserModel'               => TW_ROOT . '/models/UserModel.php',
        'AuthController'          => TW_ROOT . '/controllers/AuthController.php',
        'DashboardController'     => TW_ROOT . '/controllers/DashboardController.php',
        'SiteController'          => TW_ROOT . '/controllers/SiteController.php',
        'WidgetController'        => TW_ROOT . '/controllers/WidgetController.php',
        'LeadController'          => TW_ROOT . '/controllers/LeadController.php',
        'IntegrationController'   => TW_ROOT . '/controllers/IntegrationController.php',
        'StatsController'         => TW_ROOT . '/controllers/StatsController.php',
        'AttemptsController'      => TW_ROOT . '/controllers/AttemptsController.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});
