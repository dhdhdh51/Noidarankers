<?php
declare(strict_types=1);

namespace App\Core;

/** Database-backed fixed-window rate limiting per action and (hashed) IP. */
final class RateLimiter
{
    /** Records a hit and returns false when the limit for the window is exceeded. */
    public static function hit(string $action, int $max, int $windowSeconds): bool
    {
        $ipHash = Security::hashIp(Request::ip());
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM rate_limits WHERE action = ? AND ip_hash = ? AND created_at > ?',
            [$action, $ipHash, $since]
        );
        if ($count >= $max) {
            return false;
        }
        Database::insert('rate_limits', ['action' => $action, 'ip_hash' => $ipHash, 'created_at' => date('Y-m-d H:i:s')]);
        if (random_int(1, 50) === 1) {
            self::cleanup();
        }
        return true;
    }

    public static function cleanup(): void
    {
        Database::run('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    }
}
