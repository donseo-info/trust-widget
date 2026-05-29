<?php
/**
 * Router — maps URI patterns to [Controller, method].
 *
 * Routes are matched in registration order. Supports named captures: {id}, {slug}.
 * Unmatched request → 404 handler.
 *
 * Usage:
 *   $router = new Router();
 *   $router->get('/',               [DashboardController::class, 'index']);
 *   $router->get('/sites/{id}',     [SiteController::class, 'show']);
 *   $router->post('/sites',         [SiteController::class, 'store']);
 *   $router->dispatch();
 */
class Router
{
    private array $routes    = [];
    private array $params    = [];
    private $notFoundHandler = null;

    // ─── Route registration ──────────────────────────────────────────────────

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function any(string $path, array $handler): void
    {
        $this->add('ANY', $path, $handler);
    }

    public function setNotFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    // ─── Dispatch ────────────────────────────────────────────────────────────

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $this->parseUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }
            if ($this->match($route['pattern'], $uri)) {
                $this->invoke($route['handler']);
                return;
            }
        }

        $this->handle404();
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = $this->buildPattern($path);
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    /** Convert /sites/{id} → named regex. */
    private function buildPattern(string $path): string
    {
        $p = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $p . '$#';
    }

    /** Try to match URI against pattern; populate $this->params on success. */
    private function match(string $pattern, string $uri): bool
    {
        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }
        // Keep only named captures
        $this->params = array_filter(
            $matches,
            fn($k) => is_string($k),
            ARRAY_FILTER_USE_KEY
        );
        return true;
    }

    private function invoke(array $handler): void
    {
        [$class, $method] = $handler;
        $controller = new $class();
        $controller->$method($this->params);
    }

    private function parseUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // Strip base path when running in a subdirectory
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . ltrim($uri ?: '/', '/');
    }

    private function handle404(): void
    {
        http_response_code(404);
        if ($this->notFoundHandler) {
            ($this->notFoundHandler)();
        } else {
            echo '<h1>404 — Page Not Found</h1>';
        }
    }
}
