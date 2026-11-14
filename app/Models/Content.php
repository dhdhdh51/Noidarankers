<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Small read models for pricing plans and testimonials. */
final class Content
{
    /** @return array<int, array<string, mixed>> */
    public static function plans(): array
    {
        return Database::all("SELECT * FROM pricing_plans WHERE status = 'published' ORDER BY sort_order, id");
    }

    /** @return array<int, array<string, mixed>> */
    public static function testimonials(int $limit = 6): array
    {
        return Database::all("SELECT * FROM testimonials WHERE status = 'published' ORDER BY sort_order, id LIMIT ?", [$limit]);
    }
}
