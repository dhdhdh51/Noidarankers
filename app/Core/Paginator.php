<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public int $pages;
    public int $offset;

    public function __construct(public int $total, public int $perPage, public int $page)
    {
        $this->pages = max(1, (int) ceil($total / max(1, $perPage)));
        $this->offset = ($page - 1) * $perPage;
    }

    public function outOfRange(): bool
    {
        return $this->page < 1 || $this->page > $this->pages;
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }

    /** Page numbers to show, with null for gaps: [1, null, 4, 5, 6, null, 10]. @return array<int, int|null> */
    public function window(int $around = 1): array
    {
        $out = [];
        $last = 0;
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $this->page) <= $around) {
                if ($last && $i - $last > 1) {
                    $out[] = null;
                }
                $out[] = $i;
                $last = $i;
            }
        }
        return $out;
    }
}
