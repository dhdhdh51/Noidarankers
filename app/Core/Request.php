<?php
declare(strict_types=1);

namespace App\Core;

/** Helpers for reading the current HTTP request. */
final class Request
{
    public static function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        return $method === 'HEAD' ? 'GET' : $method;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /** Request path without the base path and query string, always starting with "/". */
    public static function path(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = rawurldecode($path);
        $path = '/' . ltrim((string) preg_replace('#/+#', '/', $path), '/');
        $base = base_path();
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
            $path = $path === '' ? '/' : $path;
        }
        return $path;
    }

    public static function queryString(): string
    {
        return (string) ($_SERVER['QUERY_STRING'] ?? '');
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (config('app.trust_proxy')) {
            $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            if ($proto === 'https') {
                return true;
            }
            $cf = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
            if ($cf !== '' && str_contains($cf, 'https')) {
                return true;
            }
        }
        return false;
    }

    public static function host(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return (string) preg_replace('/[^a-z0-9.\-:\[\]]/', '', $host);
    }

    public static function ip(): string
    {
        if (config('app.trust_proxy')) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'] as $header) {
                if (!empty($_SERVER[$header])) {
                    $candidate = trim(explode(',', (string) $_SERVER[$header])[0]);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /** Trimmed string input from POST (or GET when $fromQuery). Arrays are rejected. */
    public static function input(string $key, string $default = '', bool $fromQuery = false): string
    {
        $source = $fromQuery ? $_GET : $_POST;
        $value = $source[$key] ?? $default;
        if (!is_string($value)) {
            return $default;
        }
        // Normalise line endings and strip invisible control characters.
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return trim($value);
    }

    public static function query(string $key, string $default = ''): string
    {
        return self::input($key, $default, true);
    }

    /** Raw (untrimmed) string input, e.g. for passwords. */
    public static function raw(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? $value : '';
    }

    /** @return array<int|string, mixed> */
    public static function array(string $key): array
    {
        $value = $_POST[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    public static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }
}
