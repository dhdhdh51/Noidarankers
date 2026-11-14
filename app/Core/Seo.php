<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Per-page SEO data: title, description, canonical, robots, Open Graph,
 * Twitter cards and a JSON-LD @graph (Organization + LocalBusiness, WebSite,
 * WebPage, BreadcrumbList plus page-specific nodes added by controllers).
 */
final class Seo
{
    public const AREAS_SERVED = ['Noida', 'Greater Noida', 'Ghaziabad', 'Delhi NCR'];

    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1';
    public string $ogType = 'website';
    public string $pageType = 'WebPage';
    public bool $exactTitle = false;
    /** @var array{url:string,width:int,height:int,alt:string}|null */
    public ?array $image = null;
    /** @var array<int, array{0:string, 1:string}> */
    public array $breadcrumbs = [];
    /** @var array<int, array<string, mixed>> */
    public array $nodes = [];
    public ?string $published = null;
    public ?string $modified = null;

    public function __construct(public string $title, public string $description, public string $path)
    {
    }

    public static function page(string $title, string $description, string $path): self
    {
        return new self($title, excerpt($description, 165), $path);
    }

    public function noindex(): self
    {
        $this->robots = 'noindex, follow';
        return $this;
    }

    /** @param array<int, array{0:string, 1:string}> $items [label, path] pairs, starting with Home. */
    public function breadcrumbs(array $items): self
    {
        $this->breadcrumbs = $items;
        return $this;
    }

    public function image(string $url, int $width, int $height, string $alt): self
    {
        $this->image = ['url' => absolute_url($url), 'width' => $width, 'height' => $height, 'alt' => $alt];
        return $this;
    }

    /** @param array<string, mixed> $node */
    public function add(array $node): self
    {
        $this->nodes[] = $node;
        return $this;
    }

    public function article(?string $published, ?string $modified): self
    {
        $this->ogType = 'article';
        $this->published = $published;
        $this->modified = $modified ?: $published;
        return $this;
    }

    public function canonical(): string
    {
        return absolute_url($this->path);
    }

    public function fullTitle(): string
    {
        if ($this->exactTitle || str_contains($this->title, site_name())) {
            return $this->title;
        }
        $full = $this->title . ' | ' . site_name();
        // Keep titles within ~60 characters so Google does not cut them off.
        return mb_strlen($full) <= 60 ? $full : $this->title;
    }

    /** All <head> meta tags for this page. */
    public function head(): string
    {
        $image = $this->image ?? [
            'url' => absolute_url('/assets/img/og-default.jpg'),
            'width' => 1200,
            'height' => 630,
            'alt' => site_name() . ' – SEO agency in Noida',
        ];
        $tags = [
            '<title>' . e($this->fullTitle()) . '</title>',
            '<meta name="description" content="' . e($this->description) . '">',
            '<meta name="robots" content="' . e($this->robots) . '">',
            '<link rel="canonical" href="' . e($this->canonical()) . '">',
            '<meta property="og:locale" content="en_IN">',
            '<meta property="og:type" content="' . e($this->ogType) . '">',
            '<meta property="og:site_name" content="' . e(site_name()) . '">',
            '<meta property="og:title" content="' . e($this->fullTitle()) . '">',
            '<meta property="og:description" content="' . e($this->description) . '">',
            '<meta property="og:url" content="' . e($this->canonical()) . '">',
            '<meta property="og:image" content="' . e($image['url']) . '">',
            '<meta property="og:image:width" content="' . (int) $image['width'] . '">',
            '<meta property="og:image:height" content="' . (int) $image['height'] . '">',
            '<meta property="og:image:alt" content="' . e($image['alt']) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . e($this->fullTitle()) . '">',
            '<meta name="twitter:description" content="' . e($this->description) . '">',
            '<meta name="twitter:image" content="' . e($image['url']) . '">',
        ];
        $x = setting('social_x');
        if (preg_match('#(?:twitter|x)\.com/([A-Za-z0-9_]{1,15})#', $x, $m)) {
            $tags[] = '<meta name="twitter:site" content="@' . e($m[1]) . '">';
        }
        if ($this->ogType === 'article') {
            if ($this->published) {
                $tags[] = '<meta property="article:published_time" content="' . e(iso_date($this->published)) . '">';
            }
            if ($this->modified) {
                $tags[] = '<meta property="article:modified_time" content="' . e(iso_date($this->modified)) . '">';
            }
        }
        $gsc = setting('gsc_verification');
        if ($gsc !== '' && preg_match('/^[A-Za-z0-9_\-]{10,100}$/', $gsc)) {
            $tags[] = '<meta name="google-site-verification" content="' . e($gsc) . '">';
        }
        return implode("\n", $tags);
    }

    /** JSON-LD script tag with the full @graph. */
    public function jsonLd(): string
    {
        $url = $this->canonical();
        $home = absolute_url('/');
        $graph = [self::organization(), [
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => site_name(),
            'inLanguage' => 'en-IN',
            'publisher' => ['@id' => $home . '#organization'],
        ]];

        $page = [
            '@type' => $this->pageType,
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $this->fullTitle(),
            'description' => $this->description,
            'inLanguage' => 'en-IN',
            'isPartOf' => ['@id' => $home . '#website'],
            'about' => ['@id' => $home . '#organization'],
        ];
        if ($this->image) {
            $page['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $this->image['url']];
        }
        if (count($this->breadcrumbs) > 1) {
            $page['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
            $items = [];
            foreach ($this->breadcrumbs as $i => [$label, $path]) {
                $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => absolute_url($path)];
            }
            $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items];
        }
        $graph[] = $page;
        foreach ($this->nodes as $node) {
            $graph[] = $node;
        }

        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        return '<script type="application/ld+json" nonce="' . e(Security::nonce()) . '">' . $json . '</script>';
    }

    /** Organization + LocalBusiness node. Placeholder values are left out. @return array<string, mixed> */
    public static function organization(): array
    {
        $home = absolute_url('/');
        $node = [
            '@type' => ['Organization', 'LocalBusiness'],
            '@id' => $home . '#organization',
            'name' => site_name(),
            'url' => $home,
            'logo' => ['@type' => 'ImageObject', 'url' => absolute_url('/assets/img/logo-512.png'), 'width' => 512, 'height' => 512],
            'image' => absolute_url('/assets/img/og-default.jpg'),
            'description' => site_name() . ' is an SEO agency in Noida offering local SEO, technical SEO, content writing, link building and Google Business Profile management.',
            'areaServed' => array_map(static fn (string $a): array => ['@type' => 'Place', 'name' => $a], self::AREAS_SERVED),
            'knowsAbout' => ['Search engine optimisation', 'Local SEO', 'Technical SEO', 'Content writing', 'Link building', 'Google Business Profile'],
        ];
        $legal = setting('legal_name');
        if (!is_placeholder($legal) && $legal !== site_name()) {
            $node['legalName'] = $legal;
        }
        $phone = setting('phone');
        if (tel_href($phone) !== '') {
            $node['telephone'] = $phone;
        }
        $email = setting('email');
        if (mailto_href($email) !== '') {
            $node['email'] = $email;
        }
        $address = ['@type' => 'PostalAddress'];
        $street = array_filter([setting('address_street'), setting('address_locality')], static fn (string $v): bool => !is_placeholder($v));
        if ($street) {
            $address['streetAddress'] = implode(', ', $street);
        }
        foreach (['addressLocality' => 'address_city', 'addressRegion' => 'address_region', 'postalCode' => 'address_postal', 'addressCountry' => 'address_country'] as $prop => $key) {
            $value = setting($key);
            if (!is_placeholder($value)) {
                $address[$prop] = $value;
            }
        }
        $node['address'] = $address;
        $lat = setting('latitude');
        $lng = setting('longitude');
        if (is_numeric($lat) && is_numeric($lng)) {
            $node['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng];
        }
        $map = setting('google_maps_url');
        if (preg_match('#^https://#', $map)) {
            $node['hasMap'] = $map;
        }
        $sameAs = [];
        foreach (['social_facebook', 'social_instagram', 'social_linkedin', 'social_x', 'social_youtube'] as $key) {
            $value = setting($key);
            if (preg_match('#^https://#', $value)) {
                $sameAs[] = $value;
            }
        }
        if ($sameAs) {
            $node['sameAs'] = $sameAs;
        }
        return $node;
    }

    /** @param array<int, array{question:string, answer:string}> $faqs @return array<string, mixed> */
    public static function faqPage(array $faqs, string $path): array
    {
        $items = [];
        foreach ($faqs as $faq) {
            $items[] = [
                '@type' => 'Question',
                'name' => (string) $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) $faq['answer']],
            ];
        }
        return ['@type' => 'FAQPage', '@id' => absolute_url($path) . '#faq', 'mainEntity' => $items];
    }
}
