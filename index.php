<?php
require_once __DIR__ . '/config.php';

Auth::start();

$router = new Router();

// ─── Auth ────────────────────────────────────────────────────────────────────
$router->get('/auth/login',   [AuthController::class, 'loginPage']);
$router->post('/auth/login',  [AuthController::class, 'login']);
$router->any('/auth/logout',  [AuthController::class, 'logout']);

// ─── Dashboard ───────────────────────────────────────────────────────────────
$router->get('/',             [DashboardController::class, 'index']);
$router->get('/stats',        [StatsController::class,    'index']);

// ─── Sites ───────────────────────────────────────────────────────────────────
$router->get('/sites',                        [SiteController::class, 'index']);
$router->get('/sites/create',                 [SiteController::class, 'create']);
$router->post('/sites',                       [SiteController::class, 'store']);
$router->get('/sites/{id}',                   [SiteController::class, 'show']);
$router->get('/sites/{id}/edit',              [SiteController::class, 'edit']);
$router->post('/sites/{id}',                  [SiteController::class, 'update']);
$router->post('/sites/{id}/toggle',           [SiteController::class, 'toggle']);
$router->post('/sites/{id}/toggle-debug',     [SiteController::class, 'toggleDebug']);
$router->post('/sites/{id}/delete',           [SiteController::class, 'delete']);

// ─── Widgets ─────────────────────────────────────────────────────────────────
$router->get('/sites/{site_id}/widgets/{slug}',        [WidgetController::class, 'edit']);
$router->post('/sites/{site_id}/widgets/{slug}',       [WidgetController::class, 'save']);
$router->post('/sites/{site_id}/widgets/{slug}/toggle',[WidgetController::class, 'toggle']);

// ─── Integrations ─────────────────────────────────────────────────────────────
$router->get('/sites/{site_id}/integrations',              [IntegrationController::class, 'index']);
$router->post('/sites/{site_id}/integrations',             [IntegrationController::class, 'save']);
$router->post('/sites/{site_id}/integrations/test-telegram',[IntegrationController::class, 'testTelegram']);
$router->post('/sites/{site_id}/integrations/test-bitrix', [IntegrationController::class, 'testBitrix']);

// ─── Leads ───────────────────────────────────────────────────────────────────
$router->get('/leads',                        [LeadController::class, 'index']);
$router->post('/leads/{id}/delete',           [LeadController::class, 'delete']);

// ─── 404 ─────────────────────────────────────────────────────────────────────
$router->setNotFound(function () {
    require TW_ROOT . '/views/errors/404.php';
});

$router->dispatch();
