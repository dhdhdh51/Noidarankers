<?php
/**
 * Route table. Options:
 *   'admin' => true      admin area (auth required unless 'guest' => true)
 *   'stateless' => true  no session (sitemap, robots)
 *   'csrf' => 'manual'   controller verifies the CSRF token itself
 */
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\BlogController;
use App\Controllers\CaseStudyController;
use App\Controllers\HomeController;
use App\Controllers\LeadController;
use App\Controllers\PageController;
use App\Controllers\PricingController;
use App\Controllers\SeoController;
use App\Controllers\ServiceController;
use App\Core\Router;

return static function (Router $r): void {
    // Public pages
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/services', [ServiceController::class, 'index']);
    $r->get('/services/{slug}', [ServiceController::class, 'show']);
    $r->get('/pricing', [PricingController::class, 'index']);
    $r->get('/results', [CaseStudyController::class, 'index']);
    $r->get('/results/{slug}', [CaseStudyController::class, 'show']);
    $r->get('/blog', [BlogController::class, 'index']);
    $r->get('/blog/page/{n}', [BlogController::class, 'index']);
    $r->get('/blog/category/{slug}', [BlogController::class, 'category']);
    $r->get('/blog/category/{slug}/page/{n}', [BlogController::class, 'category']);
    $r->get('/blog/{slug}', [BlogController::class, 'show']);
    $r->get('/about', [PageController::class, 'about']);
    $r->get('/contact', [PageController::class, 'contact']);
    $r->get('/free-seo-audit', [PageController::class, 'audit']);
    $r->get('/thank-you', [PageController::class, 'thankYou']);
    $r->get('/privacy-policy', [PageController::class, 'privacy']);
    $r->get('/terms', [PageController::class, 'terms']);

    // Forms
    $r->post('/contact', [LeadController::class, 'contact'], ['csrf' => 'manual']);
    $r->post('/free-seo-audit', [LeadController::class, 'audit'], ['csrf' => 'manual']);
    $r->post('/subscribe', [LeadController::class, 'subscribe'], ['csrf' => 'manual']);

    // SEO files
    $r->get('/sitemap.xml', [SeoController::class, 'sitemap'], ['stateless' => true]);
    $r->get('/robots.txt', [SeoController::class, 'robots'], ['stateless' => true]);

    // Admin: authentication
    $guest = ['admin' => true, 'guest' => true];
    $admin = ['admin' => true];
    $r->get('/admin/setup', [Admin\AuthController::class, 'setupForm'], $guest);
    $r->post('/admin/setup', [Admin\AuthController::class, 'setup'], $guest);
    $r->get('/admin/login', [Admin\AuthController::class, 'loginForm'], $guest);
    $r->post('/admin/login', [Admin\AuthController::class, 'login'], $guest);
    $r->post('/admin/logout', [Admin\AuthController::class, 'logout'], $guest);

    // Admin: dashboard, account, uploads
    $r->get('/admin', [Admin\DashboardController::class, 'index'], $admin);
    $r->post('/admin/sample-content/delete', [Admin\DashboardController::class, 'deleteSamples'], $admin);
    $r->get('/admin/account', [Admin\AccountController::class, 'edit'], $admin);
    $r->post('/admin/account', [Admin\AccountController::class, 'update'], $admin);
    $r->post('/admin/uploads/image', [Admin\UploadController::class, 'image'], $admin);

    // Admin: leads
    $r->get('/admin/leads', [Admin\LeadsController::class, 'index'], $admin);
    $r->get('/admin/leads/export', [Admin\LeadsController::class, 'export'], $admin);
    $r->get('/admin/leads/{id}', [Admin\LeadsController::class, 'show'], $admin);
    $r->post('/admin/leads/{id}/status', [Admin\LeadsController::class, 'status'], $admin);
    $r->post('/admin/leads/{id}/notes', [Admin\LeadsController::class, 'addNote'], $admin);
    $r->post('/admin/leads/{id}/delete', [Admin\LeadsController::class, 'destroy'], $admin);

    // Admin: subscribers and settings
    $r->get('/admin/subscribers', [Admin\SubscribersController::class, 'index'], $admin);
    $r->get('/admin/subscribers/export', [Admin\SubscribersController::class, 'export'], $admin);
    $r->post('/admin/subscribers/{id}/delete', [Admin\SubscribersController::class, 'destroy'], $admin);
    $r->get('/admin/settings', [Admin\SettingsController::class, 'edit'], $admin);
    $r->post('/admin/settings', [Admin\SettingsController::class, 'update'], $admin);

    // Admin: content managers (shared CRUD routes)
    $resources = [
        'posts' => Admin\PostsController::class,
        'categories' => Admin\CategoriesController::class,
        'services' => Admin\ServicesController::class,
        'plans' => Admin\PlansController::class,
        'testimonials' => Admin\TestimonialsController::class,
        'case-studies' => Admin\CaseStudiesController::class,
    ];
    foreach ($resources as $path => $controller) {
        $r->get("/admin/$path", [$controller, 'index'], $admin);
        $r->get("/admin/$path/new", [$controller, 'create'], $admin);
        $r->post("/admin/$path", [$controller, 'store'], $admin);
        $r->get("/admin/$path/{id}/edit", [$controller, 'edit'], $admin);
        $r->post("/admin/$path/{id}", [$controller, 'update'], $admin);
        $r->post("/admin/$path/{id}/delete", [$controller, 'destroy'], $admin);
    }
};
