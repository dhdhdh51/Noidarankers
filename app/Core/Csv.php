<?php
declare(strict_types=1);

namespace App\Core;

/** Streams a CSV download that opens correctly in Excel and Google Sheets. */
final class Csv
{
    /**
     * @param array<int, string> $headers
     * @param iterable<array<int, mixed>> $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9._-]/i', '-', $filename) . '"');
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return;
        }
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_map([self::class, 'cell'], $row), ',', '"', '');
        }
        fclose($out);
        exit;
    }

    /** Prevent spreadsheet formula injection. @param mixed $value */
    public static function cell($value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
    }
}
