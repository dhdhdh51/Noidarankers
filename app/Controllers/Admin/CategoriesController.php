<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

final class CategoriesController extends ResourceController
{
    protected function table(): string { return 'categories'; }
    protected function path(): string { return 'categories'; }
    protected function singular(): string { return 'category'; }
    protected function plural(): string { return 'Blog categories'; }
    protected function orderBy(): string { return 'name'; }

    protected function columns(): array
    {
        return ['name' => 'Name', 'slug' => 'Slug', 'updated_at' => 'Updated'];
    }

    protected function publicUrl(array $row): ?string
    {
        return url('/blog/category/' . $row['slug']);
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'required' => true, 'max' => 80],
            'slug' => ['label' => 'URL slug', 'type' => 'slug', 'from' => 'name', 'max' => 100, 'help' => 'Leave empty to create it from the name.'],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'rows' => 3, 'max' => 255, 'counter' => 160, 'full' => true, 'help' => 'Shown on the category page and used as its meta description.'],
        ];
    }
}
