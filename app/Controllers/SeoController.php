<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Post;
use App\Models\Service;

/** Dynamic sitemap.xml and robots.txt. */
final class SeoController extends Controller
{
    public function sitemap(): string
    {
        $views = APP_PATH . '/Views/pages/';
        $mtime = static fn (string $file): string => is_file($views . $file) ? date('Y-m-d', (int) filemtime($views . $file)) : date('Y-m-d');
        $latest = static fn (array $dates): string => $dates ? date('Y-m-d', (int) max(array_map('strtotime', array_filter($dates)))) : date('Y-m-d');

        $services = Service::published();
        $posts = Post::allLive();
        $cases = CaseStudy::published(500);
        $homeDates = array_merge(array_column($services, 'updated_at'), array_column(array_slice($posts, 0, 3), 'updated_at'),
            [date('Y-m-d H:i:s', (int) filemtime($views . 'home.php'))]);

        $urls = [
            ['/', $latest($homeDates)],
            ['/services', $latest(array_column($services, 'updated_at') ?: [date('Y-m-d')])],
        ];
        foreach ($services as $service) {
            $urls[] = ['/services/' . $service['slug'], date('Y-m-d', (int) strtotime((string) $service['updated_at']))];
        }
        $planUpdated = (string) Database::value("SELECT MAX(updated_at) FROM pricing_plans WHERE status = 'published'");
        $urls[] = ['/pricing', $latest([$planUpdated, date('Y-m-d H:i:s', (int) filemtime($views . 'pricing.php'))])];
        if ($cases) {
            $urls[] = ['/results', $latest(array_column($cases, 'updated_at'))];
            foreach ($cases as $case) {
                $urls[] = ['/results/' . $case['slug'], date('Y-m-d', (int) strtotime((string) $case['updated_at']))];
            }
        }
        if ($posts) {
            $urls[] = ['/blog', $latest(array_column($posts, 'updated_at'))];
            foreach (Category::withCounts() as $category) {
                $urls[] = ['/blog/category/' . $category['slug'], date('Y-m-d', (int) strtotime((string) $category['last_post']))];
            }
            foreach ($posts as $post) {
                $urls[] = ['/blog/' . $post['slug'], date('Y-m-d', (int) strtotime((string) $post['updated_at']))];
            }
        }
        foreach (['/about' => 'about.php', '/contact' => 'contact.php', '/free-seo-audit' => 'audit.php', '/privacy-policy' => 'privacy.php', '/terms' => 'terms.php'] as $path => $file) {
            $urls[] = [$path, $mtime($file)];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$path, $lastmod]) {
            $xml .= '  <url><loc>' . htmlspecialchars(absolute_url($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . $lastmod . "</lastmod></url>\n";
        }
        $xml .= "</urlset>\n";

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        return $xml;
    }

    public function robots(): string
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        return "User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
    }
}
