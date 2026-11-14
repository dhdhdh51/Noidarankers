<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Small authenticated-encryption helper for secrets stored at rest
 * (e.g. the AI provider API key in the settings table). Uses AES-256-GCM
 * with a key derived from app.key. Values are prefixed "enc:" so plaintext
 * or legacy values pass through unchanged on decrypt.
 */
final class Crypto
{
    private static function key(): string
    {
        return hash('sha256', Security::key(), true);
    }

    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            return $plain;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return '';
        }
        return 'enc:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        if ($payload === '' || !str_starts_with($payload, 'enc:')) {
            return $payload;
        }
        $raw = base64_decode(substr($payload, 4), true);
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $plain = openssl_decrypt(
            substr($raw, 28),
            'aes-256-gcm',
            self::key(),
            OPENSSL_RAW_DATA,
            substr($raw, 0, 12),
            substr($raw, 12, 16)
        );
        return $plain === false ? '' : $plain;
    }
}
