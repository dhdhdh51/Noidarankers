<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Models\Service;

final class ServiceController extends Controller
{
    public function index(): string
    {
        $seo = Seo::page(
            'SEO Services in Noida',
            'SEO services in Noida: local SEO, technical SEO, content writing, link building, Google Business Profile management and SEO audits for growing businesses.',
            '/services'
        );
        $seo->pageType = 'CollectionPage';
        $seo->breadcrumbs([['Home', '/'], ['Services', '/services']]);
        $services = Service::published();
        $items = [];
        foreach ($services as $i => $service) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => absolute_url('/services/' . $service['slug']), 'name' => $service['nav_title']];
        }
        $seo->add(['@type' => 'ItemList', '@id' => absolute_url('/services') . '#services', 'itemListElement' => $items]);

        return $this->view('pages/services/index', ['seo' => $seo, 'services' => $services]);
    }

    public function show(string $slug): string
    {
        $service = Service::findPublished($slug);
        if ($service === null) {
            throw $this->notFound();
        }
        $path = '/services/' . $service['slug'];
        $faqs = Service::faqs((int) $service['id']);
        $description = $service['meta_description'] !== '' ? $service['meta_description'] : excerpt((string) $service['intro'], 160);

        $seo = Seo::page($service['meta_title'] !== '' ? $service['meta_title'] : $service['title'], $description, $path);
        $seo->breadcrumbs([['Home', '/'], ['Services', '/services'], [$service['nav_title'], $path]]);
        $seo->add([
            '@type' => 'Service',
            '@id' => absolute_url($path) . '#service',
            'name' => $service['title'],
            'serviceType' => $service['nav_title'],
            'description' => excerpt((string) $service['intro'], 300),
            'url' => absolute_url($path),
            'provider' => ['@id' => absolute_url('/') . '#organization'],
            'areaServed' => array_map(static fn (string $a): array => ['@type' => 'Place', 'name' => $a], Seo::AREAS_SERVED),
        ]);
        if ($faqs) {
            $seo->add(Seo::faqPage($faqs, $path));
        }

        $related = array_values(array_filter(Service::published(), static fn (array $s): bool => $s['slug'] !== $service['slug']));
        return $this->view('pages/services/show', [
            'seo' => $seo,
            'service' => $service,
            'faqs' => $faqs,
            'related' => array_slice($related, 0, 3),
            'css' => ['prose'],
        ]);
    }
}
