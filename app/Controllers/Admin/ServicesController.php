<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use App\Models\Service;

final class ServicesController extends ResourceController
{
    /** @var array<int, array{question:string, answer:string}> */
    private array $faqInput = [];

    protected function table(): string { return 'services'; }
    protected function path(): string { return 'services'; }
    protected function singular(): string { return 'service'; }
    protected function plural(): string { return 'Services'; }
    protected function orderBy(): string { return 'sort_order, id'; }

    protected function columns(): array
    {
        return ['nav_title' => 'Service', 'slug' => 'Slug', 'status' => 'Status', 'sort_order' => 'Order'];
    }

    protected function publicUrl(array $row): ?string
    {
        return $row['status'] === 'published' ? url('/services/' . $row['slug']) : null;
    }

    protected function fields(): array
    {
        return [
            'nav_title' => ['label' => 'Short name', 'required' => true, 'max' => 60, 'help' => 'Used on tiles and menus, e.g. "Local SEO".'],
            'title' => ['label' => 'Page heading (H1)', 'required' => true, 'max' => 150, 'help' => 'e.g. "Local SEO Services in Noida".'],
            'slug' => ['label' => 'URL slug', 'type' => 'slug', 'from' => 'nav_title', 'max' => 120, 'help' => 'The page address: /services/your-slug.'],
            'summary' => ['label' => 'Tile summary', 'type' => 'textarea', 'rows' => 2, 'required' => true, 'max' => 255, 'full' => true],
            'icon' => ['label' => 'Icon', 'type' => 'select', 'options' => Service::ICONS, 'default' => 'spark'],
            'tile_color' => ['label' => 'Tile colour', 'type' => 'select', 'options' => Service::COLORS, 'default' => 'slate'],
            'tile_size' => ['label' => 'Tile size', 'type' => 'select', 'options' => Service::SIZES, 'default' => 'normal'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'default' => '0', 'min' => 0, 'max_value' => 1000],
            'intro' => ['label' => 'Introduction', 'type' => 'textarea', 'rows' => 4, 'max' => 1000, 'full' => true, 'help' => 'Shown under the page heading.'],
            'content' => ['label' => 'Page content', 'type' => 'editor', 'full' => true],
            'meta_title' => ['label' => 'Meta title', 'max' => 70, 'counter' => 60, 'section' => 'SEO'],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'max' => 170, 'counter' => 160],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft (hidden)'], 'default' => 'published', 'section' => 'Publishing'],
        ];
    }

    protected function extraForm(?array $row, array $errors): string
    {
        $faqs = $this->faqInput;
        if (!$faqs && $row && !empty($row['id'])) {
            $faqs = Service::faqs((int) $row['id']);
        }
        return View::partial('admin/resource/faqs', ['faqs' => $faqs, 'errors' => $errors]);
    }

    protected function beforeSave(array $data, ?array $existing, array &$errors): array
    {
        $questions = Request::array('faq_question');
        $answers = Request::array('faq_answer');
        $this->faqInput = [];
        foreach ($questions as $i => $question) {
            $q = trim(is_string($question) ? $question : '');
            $a = trim(is_string($answers[$i] ?? null) ? $answers[$i] : '');
            if ($q === '' && $a === '') {
                continue;
            }
            $this->faqInput[] = ['question' => $q, 'answer' => $a];
            if ($q === '' || $a === '') {
                $errors['faqs'] = 'Each FAQ needs both a question and an answer.';
            } elseif (mb_strlen($q) > 255 || mb_strlen($a) > 2000) {
                $errors['faqs'] = 'FAQ questions must be under 255 characters and answers under 2,000.';
            }
        }
        return $data;
    }

    protected function afterSave(int $id): void
    {
        Database::transaction(function () use ($id): void {
            Database::delete('faqs', ['service_id' => $id]);
            foreach ($this->faqInput as $i => $faq) {
                Database::insert('faqs', ['service_id' => $id, 'question' => $faq['question'], 'answer' => $faq['answer'], 'sort_order' => $i + 1]);
            }
        });
    }
}
