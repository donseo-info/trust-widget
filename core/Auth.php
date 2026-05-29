<?php
/**
 * Auth — session-based authentication.
 *
 * Stub-ready for multi-user: stores user_id + role in session.
 * Call Auth::require() at the top of any protected controller method.
 */
class Auth
{
    private static bool $started = false;

    // ─── Session init ────────────────────────────────────────────────────────

    public static function start(): void
    {
        if (self::$started) return;
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;
    }

    // ─── Auth state ──────────────────────────────────────────────────────────

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['user_id']);
    }

    /** Returns current user id or null. */
    public static function id(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /** Returns current user role or null. */
    public static function role(): ?string
    {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    // ─── Login / logout ──────────────────────────────────────────────────────

    /**
     * Attempt login. Returns true on success.
     *
     * @param string $email     Plain-text email
     * @param string $password  Plain-text password
     */
    public static function attempt(string $email, string $password): bool
    {
        $db   = Database::getInstance();
        $user = $db->queryOne(
            'SELECT id, password, name, role FROM users WHERE email = ? AND is_active = 1',
            [trim($email)]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    // ─── Guard ───────────────────────────────────────────────────────────────

    /** Redirect to login if not authenticated. */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
            header('Location: ' . $base . '/auth/login');
            exit;
        }
    }

    /** Redirect with 403 if not admin. */
    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '<h1>403 — Forbidden</h1>';
            exit;
        }
    }

    // ─── CSRF token for forms ────────────────────────────────────────────────

    /** Generate form CSRF token (different from widget API token). */
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        return isset($_SESSION['csrf_token'])
            && $token !== null
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Current user's display name. */
    public static function name(): string
    {
        self::start();
        return $_SESSION['user_name'] ?? 'Admin';
    }
}
