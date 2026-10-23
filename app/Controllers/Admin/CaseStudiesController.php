<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Service;

final class CaseStudiesController extends ResourceController
{
    protected function table(): string { return 'case_studies'; }
    protected function path(): string { return 'case-studies'; }
    protected function singular(): string { return 'case study'; }
    protected function plural(): string { return 'Case studies'; }
    protected function orderBy(): string { return 'sort_order, published_at DESC, id DESC'; }
    protected function searchColumns(): array { return ['title', 'client_name', 'industry', 'summary']; }

    protected function columns(): array
    {
        return ['title' => 'Title', 'industry' => 'Industry', 'status' => 'Status', 'sort_order' => 'Order'];
    }

    protected function publicUrl(array $row): ?string
    {
        return $row['status'] === 'published' ? url('/results/' . $row['slug']) : null;
    }

    protected function fields(): array
    {
        $slugs = implode(', ', array_column(Service::published(), 'slug'));
        return [
            'title' => ['label' => 'Title', 'required' => true, 'max' => 160, 'full' => true],
            'slug' => ['label' => 'URL slug', 'type' => 'slug', 'from' => 'title', 'max' => 160, 'help' => 'The page address: /results/your-slug.'],
            'client_name' => ['label' => 'Client name', 'max' => 120, 'help' => 'Leave empty if the client prefers to stay anonymous.'],
            'industry' => ['label' => 'Industry', 'max' => 80],
            'location' => ['label' => 'Location', 'max' => 80, 'help' => 'e.g. Sector 62, Noida'],
            'summary' => ['label' => 'Summary', 'type' => 'textarea', 'rows' => 3, 'required' => true, 'max' => 300, 'full' => true],
            'metrics' => ['label' => 'Key results', 'type' => 'textarea', 'rows' => 4, 'max' => 1000, 'full' => true,
                'help' => 'One per line as "value | label", e.g. "45% | more calls from Google Maps". Use only numbers you can verify.'],
            'challenge' => ['label' => 'The challenge', 'type' => 'editor', 'full' => true],
            'solution' => ['label' => 'What we did', 'type' => 'editor', 'full' => true],
            'results' => ['label' => 'The results', 'type' => 'editor', 'full' => true],
            'services_used' => ['label' => 'Services used', 'max' => 255, 'full' => true, 'help' => 'Comma-separated service slugs. Available: ' . $slugs],
            'image' => ['label' => 'Featured image', 'type' => 'image', 'help' => 'Optional. Resized and converted to WebP automatically.'],
            'image_alt' => ['label' => 'Image alt text', 'max' => 190],
            'meta_title' => ['label' => 'Meta title', 'max' => 70, 'counter' => 60, 'section' => 'SEO'],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'max' => 170, 'counter' => 160],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft (hidden)', 'published' => 'Published'], 'default' => 'draft', 'section' => 'Publishing'],
            'published_at' => ['label' => 'Publish date', 'type' => 'datetime'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => '0', 'min' => 0, 'max_value' => 1000],
        ];
    }

    protected function beforeSave(array $data, ?array $existing, array &$errors): array
    {
        $valid = array_column(Service::published(), 'slug');
        $used = array_values(array_filter(array_map('trim', explode(',', strtolower((string) $data['services_used'])))));
        $unknown = array_diff($used, $valid);
        if ($unknown) {
            $errors['services_used'] = 'Unknown service slug(s): ' . implode(', ', $unknown) . '.';
        }
        $data['services_used'] = implode(',', $used);
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        $data['is_sample'] = 0;
        return $data;
    }
}
