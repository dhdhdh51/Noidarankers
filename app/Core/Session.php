<?php
declare(strict_types=1);

namespace App\Core;

/** Hardened PHP session with one-request flash data (messages, errors, old input). */
final class Session
{
    /** @var array<string, mixed> Flash data available during this request. */
    private static array $flashNow = [];

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $path = STORAGE_PATH . '/sessions';
        if (is_dir($path) && is_writable($path)) {
            session_save_path($path);
            // Our own folder, so we must run garbage collection ourselves.
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }
        $idle = max(30, (int) config('security.session_idle_minutes', 120));
        ini_set('session.gc_maxlifetime', (string) max(7200, $idle * 60));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        session_name('nr_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => base_path() . '/',
            'secure' => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_cache_limiter('');
        session_start();

        // Move flash data from the previous request into memory for this request.
        self::$flashNow = isset($_SESSION['_flash']) && is_array($_SESSION['_flash']) ? $_SESSION['_flash'] : [];
        unset($_SESSION['_flash']);
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /** @param mixed $value */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Store a value for the next request only. @param mixed $value */
    public static function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Read flash data set by the previous request. @return mixed */
    public static function flashed(string $key, $default = null)
    {
        return self::$flashNow[$key] ?? $default;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }
}
