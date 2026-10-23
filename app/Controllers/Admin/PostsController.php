<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Category;

final class PostsController extends ResourceController
{
    protected function table(): string { return 'posts'; }
    protected function path(): string { return 'posts'; }
    protected function singular(): string { return 'blog post'; }
    protected function plural(): string { return 'Blog posts'; }
    protected function orderBy(): string { return 'COALESCE(published_at, created_at) DESC, id DESC'; }
    protected function searchColumns(): array { return ['title', 'slug', 'excerpt']; }
    protected function reservedSlugs(): array { return ['page', 'category']; }

    protected function columns(): array
    {
        return ['title' => 'Title', 'status' => 'Status', 'published_at' => 'Published', 'updated_at' => 'Updated'];
    }

    protected function publicUrl(array $row): ?string
    {
        return $row['status'] === 'published' ? url('/blog/' . $row['slug']) : null;
    }

    protected function fields(): array
    {
        return [
            'title' => ['label' => 'Title', 'required' => true, 'max' => 160, 'full' => true],
            'slug' => ['label' => 'URL slug', 'type' => 'slug', 'from' => 'title', 'max' => 160, 'help' => 'The address of the post, e.g. /blog/your-slug. Leave empty to create it from the title.'],
            'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => ['' => 'No category'] + Category::options(), 'nullable' => true],
            'excerpt' => ['label' => 'Excerpt', 'type' => 'textarea', 'rows' => 3, 'max' => 300, 'counter' => 160, 'full' => true, 'help' => 'Short summary shown on blog cards.'],
            'content' => ['label' => 'Content', 'type' => 'editor', 'full' => true],
            'image' => ['label' => 'Featured image', 'type' => 'image', 'help' => 'JPG, PNG, WebP or GIF up to ' . (int) config('uploads.max_mb', 5) . ' MB. It is resized and converted to WebP automatically.'],
            'image_alt' => ['label' => 'Image alt text', 'max' => 190, 'help' => 'Describe the image for screen readers and Google Images.'],
            'meta_title' => ['label' => 'Meta title', 'max' => 70, 'counter' => 60, 'section' => 'SEO', 'help' => 'Shown in Google results. Leave empty to use the post title.'],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'max' => 170, 'counter' => 160, 'help' => 'Leave empty to use the excerpt.'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft (hidden)', 'published' => 'Published'], 'default' => 'draft', 'section' => 'Publishing'],
            'published_at' => ['label' => 'Publish date', 'type' => 'datetime', 'help' => 'Leave empty to use the current time when publishing. A future date schedules the post.'],
        ];
    }

    protected function beforeSave(array $data, ?array $existing, array &$errors): array
    {
        $data['category_id'] = $data['category_id'] === null || $data['category_id'] === '' ? null : (int) $data['category_id'];
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        if (trim(strip_tags((string) $data['content'])) === '' && $data['status'] === 'published') {
            $errors['content'] = 'Add some content before publishing.';
        }
        $data['is_sample'] = 0;
        return $data;
    }
}
