<?php
/**
 * Optional daily maintenance job (the site also cleans up by itself).
 *
 * cPanel > Cron Jobs > "Once per day":
 *   /usr/local/bin/php /home/USERNAME/noidarankers/cron/maintenance.php >/dev/null 2>&1
 *
 * - removes old rate-limit and login-attempt rows
 * - removes expired session files and stale CSS cache files
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$now = time();
$deleted = [
    'rate_limits' => Database::run('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', $now - 86400)])->rowCount(),
    'login_attempts' => Database::run('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', $now - 30 * 86400)])->rowCount(),
    'sessions' => 0,
    'css_cache' => 0,
];

$maxAge = max(7200, (int) config('security.session_idle_minutes', 120) * 60);
foreach (glob(STORAGE_PATH . '/sessions/sess_*') ?: [] as $file) {
    if (is_file($file) && filemtime($file) < $now - $maxAge) {
        @unlink($file) && $deleted['sessions']++;
    }
}
foreach (glob(STORAGE_PATH . '/cache/css-*.css') ?: [] as $file) {
    if (is_file($file) && filemtime($file) < $now - 7 * 86400) {
        @unlink($file) && $deleted['css_cache']++;
    }
}

echo '[' . date('Y-m-d H:i:s') . '] maintenance done: ' . json_encode($deleted) . PHP_EOL;
