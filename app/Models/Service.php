<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Service
{
    public const COLORS = ['blue' => 'Blue', 'amber' => 'Amber', 'green' => 'Green', 'slate' => 'Light grey', 'dark' => 'Dark'];
    public const SIZES = ['normal' => 'Normal (1 column)', 'wide' => 'Wide (2 columns)', 'tall' => 'Tall (2 rows)'];
    public const ICONS = ['map-pin' => 'Map pin', 'gear' => 'Gear', 'store' => 'Shop front', 'pen' => 'Pen', 'link' => 'Link',
        'search' => 'Magnifier', 'chart' => 'Chart', 'globe' => 'Globe', 'star' => 'Star', 'spark' => 'Spark', 'shield' => 'Shield', 'megaphone' => 'Megaphone'];

    /** @return array<int, array<string, mixed>> */
    public static function published(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Database::all("SELECT id, slug, title, nav_title, summary, icon, tile_color, tile_size, updated_at
                FROM services WHERE status = 'published' ORDER BY sort_order, id");
        }
        return $cache;
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $slug): ?array
    {
        return Database::one("SELECT * FROM services WHERE slug = ? AND status = 'published' LIMIT 1", [$slug]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function faqs(int $serviceId): array
    {
        return Database::all('SELECT id, question, answer FROM faqs WHERE service_id = ? ORDER BY sort_order, id', [$serviceId]);
    }

    /** @param array<int, string> $slugs @return array<int, array<string, mixed>> */
    public static function bySlugs(array $slugs): array
    {
        $slugs = array_values(array_filter($slugs, static fn ($s): bool => is_string($s) && $s !== ''));
        if ($slugs === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($slugs), '?'));
        return Database::all("SELECT slug, nav_title FROM services WHERE status = 'published' AND slug IN ($marks) ORDER BY sort_order", $slugs);
    }
}
