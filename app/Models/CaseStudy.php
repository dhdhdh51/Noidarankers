<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CaseStudy
{
    private const LIVE = "status = 'published' AND (published_at IS NULL OR published_at <= NOW())";

    /** @return array<int, array<string, mixed>> */
    public static function published(int $limit = 50): array
    {
        return Database::all('SELECT id, slug, title, client_name, industry, location, summary, metrics, image, image_width,
                image_height, image_alt, published_at, updated_at, is_sample
            FROM case_studies WHERE ' . self::LIVE . ' ORDER BY sort_order, published_at DESC, id DESC LIMIT ?', [$limit]);
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $slug): ?array
    {
        return Database::one('SELECT * FROM case_studies WHERE slug = ? AND ' . self::LIVE . ' LIMIT 1', [$slug]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function others(int $id, int $limit = 2): array
    {
        return Database::all('SELECT id, slug, title, industry, location, summary, metrics, image, image_width, image_height, image_alt
            FROM case_studies WHERE ' . self::LIVE . ' AND id <> ? ORDER BY sort_order, published_at DESC LIMIT ?', [$id, $limit]);
    }
}
