<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Builds the CSS that is inlined into each page (no render-blocking requests).
 * Source files live in public/assets/css; the minified result is cached in
 * storage/cache and rebuilt automatically when a source file changes.
 */
final class Css
{
    /** @param array<int, string> $parts */
    public static function inline(array $parts): string
    {
        $files = [];
        $stamp = '';
        foreach ($parts as $part) {
            if (!preg_match('/^[a-z0-9-]+$/', $part)) {
                continue;
            }
            $file = PUBLIC_PATH . '/assets/css/' . $part . '.css';
            if (is_file($file)) {
                $files[] = $file;
                $stamp .= $part . filemtime($file) . filesize($file);
            }
        }
        $cacheFile = STORAGE_PATH . '/cache/css-' . substr(sha1($stamp), 0, 16) . '.css';
        if (is_file($cacheFile)) {
            return (string) file_get_contents($cacheFile);
        }
        $css = '';
        foreach ($files as $file) {
            $css .= (string) file_get_contents($file) . "\n";
        }
        $css = self::minify($css);
        if (is_writable(STORAGE_PATH . '/cache')) {
            @file_put_contents($cacheFile, $css, LOCK_EX);
        }
        return $css;
    }

    public static function minify(string $css): string
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        $css = (string) preg_replace('/\s+/', ' ', $css);
        $css = (string) preg_replace('/\s*([{};,>])\s*/', '$1', $css);
        $css = (string) preg_replace('/:\s+/', ':', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }
}
