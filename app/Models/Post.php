<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Post
{
    private const LIVE = "p.status = 'published' AND p.published_at IS NOT NULL AND p.published_at <= NOW()";
    private const COLUMNS = 'p.id, p.title, p.slug, p.excerpt, p.image, p.image_width, p.image_height, p.image_alt,
        p.published_at, p.updated_at, p.category_id, c.name AS category_name, c.slug AS category_slug';

    /** @return array<int, array<string, mixed>> */
    public static function latest(int $limit, int $offset = 0, ?int $categoryId = null): array
    {
        $params = [];
        $where = self::LIVE;
        if ($categoryId !== null) {
            $where .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        $params[] = $limit;
        $params[] = $offset;
        return Database::all('SELECT ' . self::COLUMNS . ' FROM posts p LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . $where . ' ORDER BY p.published_at DESC, p.id DESC LIMIT ? OFFSET ?', $params);
    }

    public static function countLive(?int $categoryId = null): int
    {
        if ($categoryId !== null) {
            return (int) Database::value('SELECT COUNT(*) FROM posts p WHERE ' . self::LIVE . ' AND p.category_id = ?', [$categoryId]);
        }
        return (int) Database::value('SELECT COUNT(*) FROM posts p WHERE ' . self::LIVE);
    }

    /** @return array<string, mixed>|null */
    public static function findLive(string $slug): ?array
    {
        return Database::one('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM posts p
            LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND ' . self::LIVE . ' LIMIT 1', [$slug]);
    }

    /** Same-category posts first, then the newest others. @param array<string, mixed> $post @return array<int, array<string, mixed>> */
    public static function related(array $post, int $limit = 3): array
    {
        $related = [];
        if (!empty($post['category_id'])) {
            $related = Database::all('SELECT ' . self::COLUMNS . ' FROM posts p LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . self::LIVE . ' AND p.category_id = ? AND p.id <> ? ORDER BY p.published_at DESC LIMIT ?',
                [(int) $post['category_id'], (int) $post['id'], $limit]);
        }
        if (count($related) < $limit) {
            $exclude = array_merge([(int) $post['id']], array_map(static fn (array $r): int => (int) $r['id'], $related));
            $marks = implode(',', array_fill(0, count($exclude), '?'));
            $more = Database::all('SELECT ' . self::COLUMNS . ' FROM posts p LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . self::LIVE . " AND p.id NOT IN ($marks) ORDER BY p.published_at DESC LIMIT ?",
                array_merge($exclude, [$limit - count($related)]));
            $related = array_merge($related, $more);
        }
        return $related;
    }

    /** @return array<int, array<string, mixed>> For the sitemap. */
    public static function allLive(): array
    {
        return Database::all('SELECT p.slug, p.updated_at, p.published_at FROM posts p WHERE ' . self::LIVE . ' ORDER BY p.published_at DESC');
    }
}
