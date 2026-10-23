<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

final class TestimonialsController extends ResourceController
{
    protected function table(): string { return 'testimonials'; }
    protected function path(): string { return 'testimonials'; }
    protected function singular(): string { return 'testimonial'; }
    protected function plural(): string { return 'Testimonials'; }
    protected function orderBy(): string { return 'sort_order, id'; }
    protected function searchColumns(): array { return ['author_name', 'company', 'quote']; }

    protected function columns(): array
    {
        return ['author_name' => 'Client', 'company' => 'Company', 'status' => 'Status', 'sort_order' => 'Order'];
    }

    protected function publicUrl(array $row): ?string
    {
        return $row['status'] === 'published' ? url('/') . '#testimonials-heading' : null;
    }

    protected function fields(): array
    {
        return [
            'author_name' => ['label' => 'Client name', 'required' => true, 'max' => 100],
            'author_role' => ['label' => 'Role', 'max' => 100, 'help' => 'e.g. Owner, Marketing Manager'],
            'company' => ['label' => 'Company and area', 'max' => 120, 'help' => 'e.g. Smile Dental, Sector 50, Noida'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => '0', 'min' => 0, 'max_value' => 1000],
            'quote' => ['label' => 'Testimonial', 'type' => 'textarea', 'rows' => 5, 'required' => true, 'max' => 1000, 'full' => true,
                'help' => 'Only publish real words from real clients, with their permission.'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden'], 'default' => 'published'],
        ];
    }

    protected function beforeSave(array $data, ?array $existing, array &$errors): array
    {
        $data['is_sample'] = 0;
        return $data;
    }
}
