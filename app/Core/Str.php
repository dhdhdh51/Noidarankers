<?php
declare(strict_types=1);

namespace App\Core;

final class Str
{
    public static function slug(string $text, int $max = 120): string
    {
        $text = mb_strtolower(trim($text));
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = strtolower($converted);
            }
        }
        $text = str_replace(['&', '@', '+'], [' and ', ' at ', ' plus '], $text);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        if (strlen($text) > $max) {
            $text = rtrim(substr($text, 0, $max), '-');
        }
        return $text;
    }

    /** Make a slug unique in a table column, appending -2, -3, … when needed. */
    public static function uniqueSlug(string $table, string $slug, int $ignoreId = 0, array $reserved = []): string
    {
        $base = $slug !== '' ? $slug : 'item';
        $candidate = $base;
        $i = 2;
        while (in_array($candidate, $reserved, true)
            || (int) Database::value("SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id <> ?", [$candidate, $ignoreId]) > 0) {
            $candidate = $base . '-' . $i++;
        }
        return $candidate;
    }
}
