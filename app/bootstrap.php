<?php
/**
 * Application bootstrap: paths, configuration, error handling and autoloading.
 * Used by public/index.php (web) and cron/maintenance.php (CLI).
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('NoidaRankers needs PHP 8.0 or newer. Please change the PHP version in your hosting panel.');
}

$configFile = BASE_PATH . '/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "config.php not found. Copy config.sample.php to config.php first.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup required</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem;color:#0f172a">'
        . '<h1>Almost there</h1><p>The file <code>config.php</code> is missing. Copy <code>config.sample.php</code> '
        . 'to <code>config.php</code>, add your database details and reload this page.</p>'
        . '<p>See README.md for the full installation steps.</p></body></html>';
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/helpers.php';

App\Core\Config::load(require $configFile);

date_default_timezone_set((string) config('app.timezone', 'Asia/Kolkata'));
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

error_reporting(E_ALL);
ini_set('display_errors', config('app.env') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(STORAGE_PATH . '/logs') && is_writable(STORAGE_PATH . '/logs')) {
    ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
}
