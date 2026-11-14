<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Ai;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\ImageProcessor;
use App\Core\Request;
use App\Core\Settings;
use App\Core\Str;
use App\Models\Category;

/**
 * Admin > AI Writer. Configure any AI provider (link + key + model), test it,
 * and generate full SEO blog posts (with internal links and an optional
 * generated image) saved as drafts for review.
 */
final class AiController extends AdminController
{
    private const TONES = [
        'professional, helpful' => 'Professional & helpful',
        'friendly and conversational' => 'Friendly & conversational',
        'authoritative and expert' => 'Authoritative & expert',
        'persuasive and sales-focused' => 'Persuasive (sales)',
        'simple and beginner-friendly' => 'Simple (beginner-friendly)',
    ];
    private const LENGTHS = ['short' => 600, 'medium' => 1000, 'long' => 1600];

    public function form(): string
    {
        return $this->admin('ai/generate', [
            'configured' => Ai::configured(),
            'enabled' => Ai::config()['enabled'],
            'imageOn' => Ai::config()['image_enabled'],
            'categories' => Category::options(),
            'tones' => self::TONES,
        ], 'AI Writer');
    }

    public function generate(): string
    {
        @set_time_limit(180);

        if (!Ai::configured()) {
            $this->error('Add your AI provider link, key and model first.');
            redirect(url('/admin/ai/settings'));
        }

        $topic = Request::input('topic');
        $keywords = Request::input('keywords');
        $sourceUrl = Request::input('url');
        if ($topic === '' && $keywords === '' && $sourceUrl === '') {
            $this->error('Enter a topic, some keywords, or a source URL to generate from.');
            redirect(url('/admin/ai'));
        }

        $lengthKey = Request::input('length', 'auto');
        $words = self::LENGTHS[$lengthKey] ?? 0; // 0 => auto: the AI chooses the ideal length
        $categoryId = (int) Request::input('category_id');
        $makeImage = Request::input('make_image') === '1';

        try {
            $post = Ai::generateBlog([
                'topic' => $topic,
                'keywords' => $keywords,
                'url' => $sourceUrl,
                'tone' => array_key_exists(Request::input('tone'), self::TONES) ? Request::input('tone') : 'professional, helpful',
                'words' => $words,
            ]);
        } catch (\Throwable $e) {
            $this->error('Generation failed: ' . $e->getMessage());
            redirect(url('/admin/ai'));
            return '';
        }

        $image = '';
        $imageAlt = '';
        if ($makeImage && Ai::config()['image_enabled']) {
            $result = Ai::generateImage($post['image_prompt'] !== '' ? $post['image_prompt'] : $post['title'], $post['slug']);
            if ($result !== null) {
                $image = $result['path'];
                $imageAlt = $post['image_alt'];
            }
        }

        $slug = Str::uniqueSlug('posts', $post['slug'] !== '' ? $post['slug'] : Str::slug($post['title']), 0, ['page', 'category']);

        $id = Database::insert('posts', [
            'title' => $post['title'],
            'slug' => $slug,
            'excerpt' => $post['excerpt'],
            'content' => $post['content'],
            'image' => $image,
            'image_alt' => $imageAlt,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'meta_title' => $post['meta_title'],
            'meta_description' => $post['meta_description'],
            'status' => 'draft',
            'published_at' => null,
            'is_sample' => 0,
        ]);

        $this->success('Draft generated' . ($image !== '' ? ' with image' : '') . '. Review it, then publish when you are happy.');
        redirect(url('/admin/posts/' . $id . '/edit'));
        return '';
    }

    public function settings(): string
    {
        $c = Ai::config();
        return $this->admin('ai/settings', [
            'c' => $c,
            'hasKey' => setting('ai_api_key') !== '',
            'hasImageKey' => setting('ai_image_key') !== '',
            'defaultKey' => \App\Core\Security::usingDefaultKey(),
        ], 'AI Writer settings');
    }

    public function saveSettings(): string
    {
        $values = [
            'ai_enabled' => Request::input('ai_enabled') === '1' ? '1' : '0',
            'ai_base_url' => rtrim(Request::input('ai_base_url'), '/'),
            'ai_format' => Request::input('ai_format') === 'anthropic' ? 'anthropic' : 'openai',
            'ai_model' => Request::input('ai_model'),
            'ai_temperature' => (string) min(2.0, max(0.0, (float) (Request::input('ai_temperature') ?: '0.7'))),
            'ai_image_enabled' => Request::input('ai_image_enabled') === '1' ? '1' : '0',
            'ai_image_base_url' => rtrim(Request::input('ai_image_base_url'), '/'),
            'ai_image_model' => Request::input('ai_image_model'),
            'ai_image_size' => Request::input('ai_image_size') ?: '1024x1024',
        ];
        // Secrets: only overwrite when a new value is entered (blank keeps the saved one).
        $key = Request::raw('ai_api_key');
        if (trim($key) !== '') {
            $values['ai_api_key'] = Crypto::encrypt(trim($key));
        }
        $imgKey = Request::raw('ai_image_key');
        if (trim($imgKey) !== '') {
            $values['ai_image_key'] = Crypto::encrypt(trim($imgKey));
        }
        Settings::save($values);
        $this->success('AI settings saved.');
        redirect(url('/admin/ai/settings'));
        return '';
    }

    public function test(): string
    {
        header('Content-Type: application/json');
        $this->applyRuntimeConfig();
        return (string) json_encode(Ai::test());
    }

    public function models(): string
    {
        header('Content-Type: application/json');
        $this->applyRuntimeConfig();
        return (string) json_encode(['models' => Ai::models()]);
    }

    /**
     * Let "Test connection" and "Load models" use the values currently typed in
     * the form (before Save), so first-time setup works in one go. Blank fields
     * fall back to whatever is already saved.
     */
    private function applyRuntimeConfig(): void
    {
        $override = [];
        $base = rtrim(Request::input('ai_base_url'), '/');
        if ($base !== '') {
            $override['base_url'] = $base;
        }
        $model = trim(Request::input('ai_model'));
        if ($model !== '') {
            $override['model'] = $model;
        }
        $format = Request::input('ai_format');
        if ($format !== '') {
            $override['format'] = $format === 'anthropic' ? 'anthropic' : 'openai';
        }
        $key = trim(Request::raw('ai_api_key'));
        if ($key !== '') {
            $override['api_key'] = $key;
        }
        if ($override !== []) {
            Ai::useRuntimeConfig($override);
        }
    }
}
