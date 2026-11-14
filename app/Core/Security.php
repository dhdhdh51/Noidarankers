<?php
declare(strict_types=1);

namespace App\Core;

/** Security headers, CSP nonce and the signed timer used against form bots. */
final class Security
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }
        return self::$nonce;
    }

    /** Send CSP and related headers. $admin relaxes inline styles for the editor. */
    public static function sendHeaders(bool $admin = false): void
    {
        if (headers_sent()) {
            return;
        }
        $nonce = self::nonce();
        $ga = !$admin && ga_id() !== '';
        $gaScript = $ga ? ' https://www.googletagmanager.com' : '';
        $gaConnect = $ga ? ' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com' : '';
        $gaImg = $ga ? ' https://*.google-analytics.com https://*.googletagmanager.com' : '';
        $style = $admin ? "'self' 'unsafe-inline'" : "'self' 'nonce-{$nonce}'";

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$gaScript}",
            "style-src {$style}",
            "img-src 'self' data:{$gaImg}",
            "font-src 'self'",
            "connect-src 'self'{$gaConnect}",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];
        if (Request::isHttps()) {
            $csp[] = 'upgrade-insecure-requests';
        }
        header('Content-Security-Policy: ' . implode('; ', $csp));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        if (Request::isHttps() && config('app.force_https')) {
            header('Strict-Transport-Security: max-age=31536000');
        }
        header_remove('X-Powered-By');
    }

    /** Signed timestamp placed in forms; proves the form was loaded and when. */
    public static function formTimer(): string
    {
        $time = (string) time();
        return $time . '.' . substr(hash_hmac('sha256', 'form-timer|' . $time, self::key()), 0, 32);
    }

    /**
     * Seconds since the form was rendered, or null when the token is missing/forged.
     */
    public static function formAge(string $token): ?int
    {
        if (!preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $token, $m)) {
            return null;
        }
        $expected = substr(hash_hmac('sha256', 'form-timer|' . $m[1], self::key()), 0, 32);
        if (!hash_equals($expected, $m[2])) {
            return null;
        }
        return time() - (int) $m[1];
    }

    public static function hashIp(string $ip): string
    {
        return hash_hmac('sha256', 'ip|' . $ip, self::key());
    }

    public static function key(): string
    {
        $key = (string) config('app.key', '');
        return $key !== '' ? $key : 'noidarankers-default-key';
    }

    public static function usingDefaultKey(): bool
    {
        $key = (string) config('app.key', '');
        return $key === '' || str_starts_with($key, 'CHANGE-ME') || strlen($key) < 32;
    }
}
