<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Seo;
use App\Models\Content;

final class PricingController extends Controller
{
    public const FAQS = [
        ['question' => 'How long does SEO take to show results?', 'answer' => 'SEO builds over time. Fixes to your Google Business Profile and website can help within weeks, while stronger results from content and links usually build over three to six months. Competitive searches can take longer.'],
        ['question' => 'Which plan is right for my business?', 'answer' => 'Starter suits a single-location business that mainly needs local visibility. Growth adds regular content, technical fixes and link building for businesses that want steady growth. Pro is for competitive industries and businesses with several locations. Not sure? Start with a free SEO audit and we will recommend a plan.'],
        ['question' => 'Can you guarantee first-page rankings?', 'answer' => 'No honest agency can guarantee rankings, because Google decides them. We focus on the work that moves rankings, calls and enquiries in the right direction, and we report on it every month.'],
    ];

    public function index(): string
    {
        $seo = Seo::page(
            'SEO Pricing Plans',
            'Simple monthly SEO plans for Noida businesses: Starter, Growth and Pro. Compare what is included in each plan and start with a free SEO audit.',
            '/pricing'
        );
        $seo->breadcrumbs([['Home', '/'], ['Pricing', '/pricing']]);
        $seo->add(Seo::faqPage(self::FAQS, '/pricing'));

        return $this->view('pages/pricing', ['seo' => $seo, 'plans' => Content::plans(), 'faqs' => self::FAQS]);
    }
}
