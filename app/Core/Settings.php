<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Site settings. Values saved in Admin > Settings (database) override the
 * defaults from config.php. Unknown keys fall back to the defaults below.
 */
final class Settings
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /** Editable settings with their default values (config.php keys win where noted). */
    public static function defaults(): array
    {
        return [
            'phone' => (string) config('site.phone', '[YOUR PHONE]'),
            'email' => (string) config('site.email', '[YOUR EMAIL]'),
            'whatsapp' => (string) config('site.whatsapp', '[YOUR WHATSAPP NUMBER]'),
            'notify_email' => (string) config('site.notify_email', (string) config('site.email', '')),
            'legal_name' => (string) config('site.name', 'NoidaRankers'),
            'address_street' => '[YOUR STREET ADDRESS]',
            'address_locality' => 'Sector [XX]',
            'address_city' => 'Noida',
            'address_region' => 'Uttar Pradesh',
            'address_postal' => '[PIN CODE]',
            'address_country' => 'IN',
            'opening_hours' => '[OPENING HOURS]',
            'google_maps_url' => '',
            'latitude' => '',
            'longitude' => '',
            'social_facebook' => '',
            'social_instagram' => '',
            'social_linkedin' => '',
            'social_x' => '',
            'social_youtube' => '',
            'ga_id' => '',
            'gsc_verification' => '',
            'stat1_value' => '[X]%',
            'stat1_label' => 'Average growth in organic traffic',
            'stat2_value' => '[X]+',
            'stat2_label' => 'Keywords moved to page one of Google',
            'stat3_value' => '[X]x',
            'stat3_label' => 'More calls and direction requests from Google Maps',
            'stat4_value' => '[X]',
            'stat4_label' => 'Businesses in Noida and NCR we have worked with',
            'policies_updated' => '2026-10-23',
        ];
    }

    public static function get(string $key, string $default = ''): string
    {
        $stored = self::all()[$key] ?? '';
        if (trim($stored) !== '') {
            return $stored;
        }
        return self::defaults()[$key] ?? $default;
    }

    /** @return array<string, string> Values stored in the database. */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::all('SELECT setting_key, setting_value FROM settings') as $row) {
                    self::$cache[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
                }
            } catch (\Throwable $e) {
                error_log('Settings could not be loaded: ' . $e->getMessage());
            }
        }
        return self::$cache;
    }

    /** @param array<string, string> $values */
    public static function save(array $values): void
    {
        $allowed = self::defaults();
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $allowed)) {
                continue;
            }
            Database::run(
                'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$key, $value]
            );
        }
        self::$cache = null;
    }
}
