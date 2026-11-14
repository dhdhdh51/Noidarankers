<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    /** Categories with their number of live posts. @return array<int, array<string, mixed>> */
    public static function withCounts(bool $onlyUsed = true): array
    {
        $rows = Database::all("SELECT c.id, c.name, c.slug, c.description, c.updated_at,
                (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id AND p.status = 'published'
                    AND p.published_at IS NOT NULL AND p.published_at <= NOW()) AS post_count,
                (SELECT MAX(p.updated_at) FROM posts p WHERE p.category_id = c.id AND p.status = 'published'
                    AND p.published_at IS NOT NULL AND p.published_at <= NOW()) AS last_post
            FROM categories c ORDER BY c.name");
        return $onlyUsed ? array_values(array_filter($rows, static fn (array $r): bool => (int) $r['post_count'] > 0)) : $rows;
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    /** @return array<int, string> id => name for select boxes */
    public static function options(): array
    {
        $out = [];
        foreach (Database::all('SELECT id, name FROM categories ORDER BY name') as $row) {
            $out[(int) $row['id']] = (string) $row['name'];
        }
        return $out;
    }
}
