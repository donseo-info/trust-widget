<?php
/**
 * Controller — base class for all controllers.
 *
 * Provides helpers: render(), json(), redirect(), abort().
 */
abstract class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::start();
    }

    // ─── Rendering ───────────────────────────────────────────────────────────

    /**
     * Render a view inside the admin layout.
     *
     * @param string $view   Path relative to views/ e.g. 'admin/dashboard'
     * @param array  $data   Variables extracted into view scope
     */
    protected function render(string $view, array $data = []): void
    {
        $data['_view']        = $view;
        $data['_csrfToken']   = Auth::csrfToken();
        $data['__csrfToken']  = Auth::csrfToken();
        $data['_userName']    = Auth::name();
        $data['_userRole']    = Auth::role();
        extract($data, EXTR_SKIP);

        $viewFile = TW_ROOT . '/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            $this->abort(500, "View not found: {$view}");
        }

        // Capture view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Wrap in layout
        require TW_ROOT . '/views/layout/main.php';
    }

    /**
     * Render a view WITHOUT layout (for modal/AJAX partials).
     */
    protected function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require TW_ROOT . '/views/' . $view . '.php';
    }

    // ─── Responses ───────────────────────────────────────────────────────────

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $url): void
    {
        // Prepend base path if app lives in a subdirectory
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base !== '' && str_starts_with($url, '/') && !str_starts_with($url, $base)) {
            $url = $base . $url;
        }
        header('Location: ' . $url);
        exit;
    }

    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        echo "<h1>{$code}</h1><p>" . htmlspecialchars($message) . '</p>';
        exit;
    }

    // ─── Request helpers ─────────────────────────────────────────────────────

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /** Get sanitized POST value. */
    protected function post(string $key, string $default = ''): string
    {
        return trim((string)($_POST[$key] ?? $default));
    }

    /** Get sanitized GET value. */
    protected function get(string $key, string $default = ''): string
    {
        return trim((string)($_GET[$key] ?? $default));
    }

    /** Validate and return CSRF token from POST, abort 403 on fail. */
    protected function requireCsrf(): void
    {
        if (!Auth::verifyCsrf($this->post('_csrf'))) {
            $this->abort(403, 'CSRF token mismatch');
        }
    }
}
