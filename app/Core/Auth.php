<?php
declare(strict_types=1);

namespace App\Core;

/** Admin authentication with brute-force lockout and session hardening. */
final class Auth
{
    /** @var array<string, mixed>|null|false */
    private static $user = false;

    /**
     * @return array{ok:bool, error:string}
     */
    public static function attempt(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $ip = Request::ip();

        $wait = self::lockoutSeconds($email, $ip);
        if ($wait > 0) {
            return ['ok' => false, 'error' => sprintf('Too many failed attempts. Please try again in %d minute(s).', (int) ceil($wait / 60))];
        }

        $user = Database::one('SELECT * FROM admin_users WHERE email = ? LIMIT 1', [$email]);
        // Always run password_verify so response time does not reveal valid emails.
        $hash = $user['password_hash'] ?? '$2y$12$nzXUaDrPpvXMDRL5VQQyCeH3t0GeJuKTEPES4TxuMOHD35asWpNoq';
        $valid = password_verify($password, (string) $hash) && $user !== null;

        Database::insert('login_attempts', ['email' => mb_substr($email, 0, 190), 'ip' => $ip, 'success' => $valid ? 1 : 0]);

        if (!$valid) {
            $wait = self::lockoutSeconds($email, $ip);
            if ($wait > 0) {
                return ['ok' => false, 'error' => sprintf('Too many failed attempts. Please try again in %d minute(s).', (int) ceil($wait / 60))];
            }
            return ['ok' => false, 'error' => 'The email or password is incorrect.'];
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('admin_users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => (int) $user['id']]);
        }
        // Successful login clears earlier failures for this account and IP.
        Database::run('DELETE FROM login_attempts WHERE success = 0 AND (email = ? OR ip = ?)', [$email, $ip]);
        Database::update('admin_users', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip], ['id' => (int) $user['id']]);

        self::login((int) $user['id']);
        return ['ok' => true, 'error' => ''];
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        Csrf::rotate();
        $_SESSION['admin_id'] = $userId;
        $_SESSION['admin_seen'] = time();
        $_SESSION['admin_ua'] = hash('sha256', Request::userAgent());
        self::$user = false;
    }

    /** Seconds the account/IP must wait, or 0 when not locked. */
    public static function lockoutSeconds(string $email, string $ip): int
    {
        $max = max(3, (int) config('security.login_max_attempts', 5));
        $minutes = max(1, (int) config('security.login_lockout_minutes', 15));
        $since = date('Y-m-d H:i:s', time() - $minutes * 60);

        $byEmail = Database::one(
            'SELECT COUNT(*) AS n, MAX(created_at) AS last FROM login_attempts WHERE email = ? AND success = 0 AND created_at > ?',
            [$email, $since]
        );
        $byIp = Database::one(
            'SELECT COUNT(*) AS n, MAX(created_at) AS last FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > ?',
            [$ip, $since]
        );
        $last = null;
        if ((int) ($byEmail['n'] ?? 0) >= $max) {
            $last = (string) $byEmail['last'];
        }
        if ((int) ($byIp['n'] ?? 0) >= $max * 2) {
            $last = max((string) $last, (string) $byIp['last']);
        }
        if ($last === null || $last === '') {
            return 0;
        }
        return max(0, (int) strtotime($last) + $minutes * 60 - time());
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== false) {
            return self::$user;
        }
        self::$user = null;
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $idle = max(5, (int) config('security.session_idle_minutes', 120)) * 60;
        $seen = (int) ($_SESSION['admin_seen'] ?? 0);
        $uaOk = hash_equals((string) ($_SESSION['admin_ua'] ?? ''), hash('sha256', Request::userAgent()));
        if (time() - $seen > $idle || !$uaOk) {
            self::logout();
            Session::start();
            Session::flash('error', 'You were signed out after a period of inactivity. Please sign in again.');
            return null;
        }
        $user = Database::one('SELECT id, name, email, last_login_at FROM admin_users WHERE id = ?', [$id]);
        if ($user === null) {
            self::logout();
            return null;
        }
        $_SESSION['admin_seen'] = time();
        self::$user = $user;
        return $user;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
    }

    public static function hasAdmin(): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM admin_users') > 0;
    }
}
