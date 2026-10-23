<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paginator;
use App\Core\Seo;
use App\Models\Category;
use App\Models\Post;

final class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(string $n = '1'): string
    {
        $page = $this->pageNumber($n);
        if ($page < 1) {
            throw $this->notFound();
        }
        if ($n === '1' && str_contains(\App\Core\Request::path(), '/page/')) {
            redirect(absolute_url('/blog'), 301);
        }
        $pager = new Paginator(Post::countLive(), self::PER_PAGE, $page);
        if ($pager->outOfRange() && $pager->total > 0) {
            throw $this->notFound();
        }
        $path = $page > 1 ? '/blog/page/' . $page : '/blog';
        $seo = Seo::page(
            $page > 1 ? "SEO Blog – Page $page" : 'SEO Blog: Tips for Noida Businesses',
            'Practical SEO advice for local businesses in Noida and Delhi NCR: local SEO, Google Business Profile, technical SEO and content marketing.',
            $path
        );
        $seo->pageType = 'CollectionPage';
        $seo->breadcrumbs($page > 1 ? [['Home', '/'], ['Blog', '/blog'], ["Page $page", $path]] : [['Home', '/'], ['Blog', '/blog']]);
        if ($pager->total === 0) {
            $seo->noindex();
        }
        return $this->view('pages/blog/index', [
            'seo' => $seo,
            'posts' => Post::latest(self::PER_PAGE, $pager->offset),
            'pager' => $pager,
            'link' => static fn (int $p): string => url($p > 1 ? '/blog/page/' . $p : '/blog'),
            'categories' => Category::withCounts(),
            'category' => null,
        ]);
    }

    public function category(string $slug, string $n = '1'): string
    {
        $category = Category::findBySlug($slug);
        if ($category === null) {
            throw $this->notFound();
        }
        $base = '/blog/category/' . $category['slug'];
        if ($n === '1' && str_contains(\App\Core\Request::path(), '/page/')) {
            redirect(absolute_url($base), 301);
        }
        $page = $this->pageNumber($n);
        if ($page < 1) {
            throw $this->notFound();
        }
        $pager = new Paginator(Post::countLive((int) $category['id']), self::PER_PAGE, $page);
        if ($pager->outOfRange() && $pager->total > 0) {
            throw $this->notFound();
        }
        $path = $page > 1 ? $base . '/page/' . $page : $base;
        $seo = Seo::page(
            $category['name'] . ' Articles' . ($page > 1 ? " – Page $page" : ''),
            $category['description'] !== '' ? $category['description'] : 'Articles about ' . $category['name'] . ' from the NoidaRankers SEO blog.',
            $path
        );
        $seo->pageType = 'CollectionPage';
        $seo->breadcrumbs([['Home', '/'], ['Blog', '/blog'], [$category['name'], $base]]);
        if ($pager->total === 0) {
            $seo->noindex();
        }
        return $this->view('pages/blog/index', [
            'seo' => $seo,
            'posts' => Post::latest(self::PER_PAGE, $pager->offset, (int) $category['id']),
            'pager' => $pager,
            'link' => static fn (int $p): string => url($p > 1 ? $base . '/page/' . $p : $base),
            'categories' => Category::withCounts(),
            'category' => $category,
        ]);
    }

    public function show(string $slug): string
    {
        $post = Post::findLive($slug);
        if ($post === null) {
            throw $this->notFound();
        }
        $path = '/blog/' . $post['slug'];
        $seo = Seo::page(
            $post['meta_title'] !== '' ? $post['meta_title'] : $post['title'],
            $post['meta_description'] !== '' ? $post['meta_description'] : ($post['excerpt'] !== '' ? $post['excerpt'] : excerpt((string) $post['content'])),
            $path
        );
        $crumbs = [['Home', '/'], ['Blog', '/blog']];
        if (!empty($post['category_slug'])) {
            $crumbs[] = [(string) $post['category_name'], '/blog/category/' . $post['category_slug']];
        }
        $crumbs[] = [excerpt((string) $post['title'], 60), $path];
        $seo->breadcrumbs($crumbs);
        $seo->article((string) $post['published_at'], (string) $post['updated_at']);
        if ($post['image'] !== '') {
            $seo->image('/' . $post['image'], (int) $post['image_width'], (int) $post['image_height'], (string) ($post['image_alt'] ?: $post['title']));
        }
        $seo->add(CaseStudyController::articleNode((string) $post['title'], (string) ($post['excerpt'] ?: $post['meta_description']), $path,
            (string) $post['published_at'], (string) $post['updated_at'], $seo, (string) ($post['category_name'] ?: 'SEO')));

        return $this->view('pages/blog/show', [
            'seo' => $seo,
            'post' => $post,
            'related' => Post::related($post),
            'css' => ['prose'],
        ]);
    }
}
