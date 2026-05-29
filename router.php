<?php
// PHP built-in server router — replaces .htaccess mod_rewrite
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve real files (css, js, images, api, widgets) directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Block sensitive dirs
if (preg_match('#^/(core|models|controllers|views|logs|db)/#', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/index.php';
