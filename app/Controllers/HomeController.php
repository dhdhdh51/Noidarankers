<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Models\Content;
use App\Models\Post;
use App\Models\Service;

final class HomeController extends Controller
{
    public function index(): string
    {
        $seo = Seo::page(
            'SEO Agency in Noida – Rank Higher on Google',
            'NoidaRankers is an SEO agency in Noida. Local SEO, technical SEO, content and Google Business Profile services that bring you more customers from Google.',
            '/'
        );
        $stats = [];
        for ($i = 1; $i <= 4; $i++) {
            $stats[] = ['value' => setting("stat{$i}_value"), 'label' => setting("stat{$i}_label")];
        }
        return $this->view('pages/home', [
            'seo' => $seo,
            'services' => Service::published(),
            'plans' => Content::plans(),
            'testimonials' => Content::testimonials(3),
            'posts' => Post::latest(3),
            'stats' => $stats,
        ]);
    }
}
