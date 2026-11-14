<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class MapLead
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'won' => 'Won', 'lost' => 'Lost'];

    public static function exists(string $placeId): bool
    {
        if ($placeId === '') {
            return false;
        }
        return (int) Database::value('SELECT COUNT(*) FROM map_leads WHERE place_id = ?', [$placeId]) > 0;
    }

    /**
     * Save a batch of search results, skipping businesses already saved.
     *
     * @param array<int,array<string,mixed>> $results
     * @return array{added:int,duplicates:int,skipped:int}
     */
    public static function saveMany(array $results, string $query): array
    {
        $added = 0;
        $dup = 0;
        $skipped = 0;
        foreach ($results as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $placeId = (string) ($r['place_id'] ?? '');
            if ($name === '' || $placeId === '') {
                $skipped++;
                continue;
            }
            if (self::exists($placeId)) {
                $dup++;
                continue;
            }
            Database::insert('map_leads', [
                'place_id' => $placeId,
                'name' => mb_substr($name, 0, 190),
                'category' => mb_substr((string) ($r['category'] ?? ''), 0, 120),
                'phone' => mb_substr((string) ($r['phone'] ?? ''), 0, 40),
                'website' => mb_substr((string) ($r['website'] ?? ''), 0, 255),
                'address' => mb_substr((string) ($r['address'] ?? ''), 0, 400),
                'rating' => isset($r['rating']) && $r['rating'] !== null ? (float) $r['rating'] : null,
                'reviews' => (int) ($r['reviews'] ?? 0),
                'maps_url' => mb_substr((string) ($r['maps_url'] ?? ''), 0, 400),
                'search_query' => mb_substr($query, 0, 255),
                'status' => 'new',
            ]);
            $added++;
        }
        return ['added' => $added, 'duplicates' => $dup, 'skipped' => $skipped];
    }

    /**
     * Admin search filter. @return array{0:string,1:array<int,mixed>}
     */
    public static function filters(string $q, string $status): array
    {
        $where = ['1=1'];
        $params = [];
        if ($status !== '' && isset(self::STATUSES[$status])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(name LIKE ? OR phone LIKE ? OR website LIKE ? OR address LIKE ? OR category LIKE ? OR search_query LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        return [implode(' AND ', $where), $params];
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM map_leads WHERE id = ?', [$id]);
    }
}
