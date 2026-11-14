<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Models\CaseStudy;
use App\Models\Service;

final class CaseStudyController extends Controller
{
    public function index(): string
    {
        $cases = CaseStudy::published();
        $seo = Seo::page(
            'SEO Results and Case Studies',
            'SEO case studies from NoidaRankers: how we help businesses in Noida and Delhi NCR get found on Google, with the work we did and the results.',
            '/results'
        );
        $seo->pageType = 'CollectionPage';
        $seo->breadcrumbs([['Home', '/'], ['Results', '/results']]);
        if ($cases === []) {
            $seo->noindex();
        }
        return $this->view('pages/results/index', ['seo' => $seo, 'cases' => $cases]);
    }

    public function show(string $slug): string
    {
        $case = CaseStudy::findPublished($slug);
        if ($case === null) {
            throw $this->notFound();
        }
        $path = '/results/' . $case['slug'];
        $seo = Seo::page(
            $case['meta_title'] !== '' ? $case['meta_title'] : $case['title'],
            $case['meta_description'] !== '' ? $case['meta_description'] : (string) $case['summary'],
            $path
        );
        $seo->breadcrumbs([['Home', '/'], ['Results', '/results'], [excerpt((string) $case['title'], 60), $path]]);
        $seo->article((string) $case['published_at'], (string) $case['updated_at']);
        if ($case['image'] !== '') {
            $seo->image('/' . $case['image'], (int) $case['image_width'], (int) $case['image_height'], (string) ($case['image_alt'] ?: $case['title']));
        }
        $seo->add(self::articleNode($case['title'], (string) $case['summary'], $path, (string) $case['published_at'], (string) $case['updated_at'], $seo, 'Case studies'));

        $services = Service::bySlugs(array_map('trim', explode(',', (string) $case['services_used'])));
        return $this->view('pages/results/show', [
            'seo' => $seo,
            'case' => $case,
            'metrics' => metric_lines((string) $case['metrics']),
            'services' => $services,
            'others' => CaseStudy::others((int) $case['id']),
            'css' => ['prose'],
        ]);
    }

    /** Shared Article JSON-LD node for posts and case studies. @return array<string, mixed> */
    public static function articleNode(string $headline, string $description, string $path, string $published, string $modified, Seo $seo, string $section): array
    {
        $image = $seo->image['url'] ?? absolute_url('/assets/img/og-default.jpg');
        return [
            '@type' => 'Article',
            '@id' => absolute_url($path) . '#article',
            'headline' => mb_substr($headline, 0, 110),
            'description' => excerpt($description, 200),
            'image' => [$image],
            'datePublished' => iso_date($published),
            'dateModified' => iso_date($modified ?: $published),
            'author' => ['@type' => 'Organization', 'name' => site_name(), 'url' => absolute_url('/about')],
            'publisher' => ['@id' => absolute_url('/') . '#organization'],
            'mainEntityOfPage' => ['@id' => absolute_url($path) . '#webpage'],
            'articleSection' => $section,
            'inLanguage' => 'en-IN',
        ];
    }
}
