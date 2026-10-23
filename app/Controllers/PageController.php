<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Seo;

final class PageController extends Controller
{
    public function about(): string
    {
        $seo = Seo::page(
            'About NoidaRankers',
            'NoidaRankers is an SEO agency in Noida that helps local businesses grow with honest, transparent SEO: local SEO, technical SEO, content and links.',
            '/about'
        );
        $seo->pageType = 'AboutPage';
        $seo->breadcrumbs([['Home', '/'], ['About', '/about']]);
        return $this->view('pages/about', ['seo' => $seo]);
    }

    public function contact(): string
    {
        $seo = Seo::page(
            'Contact NoidaRankers – SEO Agency in Noida',
            'Contact NoidaRankers, an SEO agency in Noida. Call, WhatsApp or send us a message and we will get back to you about growing your business on Google.',
            '/contact'
        );
        $seo->pageType = 'ContactPage';
        $seo->breadcrumbs([['Home', '/'], ['Contact', '/contact']]);
        return $this->view('pages/contact', ['seo' => $seo]);
    }

    public function audit(): string
    {
        $seo = Seo::page(
            'Free SEO Audit for Your Website',
            'Request a free SEO audit from NoidaRankers. We review your website, Google Business Profile and competitors, then send a clear, prioritised action plan.',
            '/free-seo-audit'
        );
        $seo->breadcrumbs([['Home', '/'], ['Free SEO audit', '/free-seo-audit']]);
        return $this->view('pages/audit', ['seo' => $seo]);
    }

    public function thankYou(): string
    {
        $type = Request::query('type') === 'audit' ? 'audit' : 'contact';
        $seo = Seo::page('Thank you', 'Thank you for contacting NoidaRankers. We have received your details and will be in touch soon.', '/thank-you');
        $seo->noindex();
        return $this->view('pages/thank-you', ['seo' => $seo, 'type' => $type, 'name' => (string) \App\Core\Session::flashed('lead_name', '')]);
    }

    public function privacy(): string
    {
        $seo = Seo::page('Privacy Policy', 'How NoidaRankers collects, uses and protects the personal information you share through this website.', '/privacy-policy');
        $seo->breadcrumbs([['Home', '/'], ['Privacy policy', '/privacy-policy']]);
        return $this->view('pages/privacy', ['seo' => $seo, 'css' => ['prose']]);
    }

    public function terms(): string
    {
        $seo = Seo::page('Terms of Service', 'The terms that apply when you use the NoidaRankers website and our SEO services.', '/terms');
        $seo->breadcrumbs([['Home', '/'], ['Terms', '/terms']]);
        return $this->view('pages/terms', ['seo' => $seo, 'css' => ['prose']]);
    }
}
