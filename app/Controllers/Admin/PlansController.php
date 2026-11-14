<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

final class PlansController extends ResourceController
{
    protected function table(): string { return 'pricing_plans'; }
    protected function path(): string { return 'plans'; }
    protected function singular(): string { return 'pricing plan'; }
    protected function plural(): string { return 'Pricing plans'; }
    protected function orderBy(): string { return 'sort_order, id'; }

    protected function columns(): array
    {
        return ['name' => 'Plan', 'price_label' => 'Price', 'is_featured' => 'Highlighted', 'status' => 'Status', 'sort_order' => 'Order'];
    }

    protected function publicUrl(array $row): ?string
    {
        return url('/pricing');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Plan name', 'required' => true, 'max' => 60],
            'price_label' => ['label' => 'Price', 'required' => true, 'max' => 40, 'default' => '[₹ PRICE]', 'help' => 'Shown exactly as typed, e.g. "₹14,999" or "From ₹9,999".'],
            'billing_period' => ['label' => 'Billing period', 'max' => 40, 'default' => 'per month', 'help' => 'e.g. "per month" or "+ GST per month".'],
            'cta_label' => ['label' => 'Button text', 'max' => 40, 'default' => 'Get started'],
            'description' => ['label' => 'Short description', 'type' => 'textarea', 'rows' => 2, 'max' => 255, 'full' => true],
            'features' => ['label' => 'Features', 'type' => 'textarea', 'rows' => 7, 'max' => 3000, 'full' => true, 'help' => 'One feature per line.'],
            'is_featured' => ['label' => 'Highlight this plan as "Most popular"', 'type' => 'checkbox'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => '0', 'min' => 0, 'max_value' => 1000],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden'], 'default' => 'published'],
        ];
    }
}
