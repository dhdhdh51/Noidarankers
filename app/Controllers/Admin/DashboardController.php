<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\ImageProcessor;
use App\Core\Security;

final class DashboardController extends AdminController
{
    public function index(): string
    {
        $counts = [
            'new' => (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'"),
            'leads' => (int) Database::value('SELECT COUNT(*) FROM leads'),
            'posts' => (int) Database::value("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
            'drafts' => (int) Database::value("SELECT COUNT(*) FROM posts WHERE status = 'draft'"),
            'subscribers' => (int) Database::value("SELECT COUNT(*) FROM subscribers WHERE status = 'subscribed'"),
        ];
        $recent = Database::all('SELECT id, type, status, name, business_type, created_at, is_sample FROM leads ORDER BY created_at DESC, id DESC LIMIT 6');
        return $this->admin('dashboard', [
            'counts' => $counts,
            'recent' => $recent,
            'samples' => $this->sampleCounts(),
            'checklist' => $this->checklist(),
        ], 'Dashboard');
    }

    public function deleteSamples(): void
    {
        foreach (Database::all('SELECT image FROM posts WHERE is_sample = 1 UNION ALL SELECT image FROM case_studies WHERE is_sample = 1') as $row) {
            ImageProcessor::delete((string) $row['image']);
        }
        $total = 0;
        foreach (['testimonials', 'case_studies', 'posts', 'leads'] as $table) {
            $total += Database::run("DELETE FROM `$table` WHERE is_sample = 1")->rowCount();
        }
        $this->success("Deleted $total sample item(s). Your own content was not touched.");
        redirect(url('/admin'));
    }

    /** @return array<string, int> */
    private function sampleCounts(): array
    {
        $out = [];
        foreach (['posts' => 'Blog posts', 'case_studies' => 'Case studies', 'testimonials' => 'Testimonials', 'leads' => 'Leads'] as $table => $label) {
            $n = (int) Database::value("SELECT COUNT(*) FROM `$table` WHERE is_sample = 1");
            if ($n > 0) {
                $out[$label] = $n;
            }
        }
        return $out;
    }

    /** Launch checklist: [label, ok, help link]. @return array<int, array{0:string,1:bool,2:string}> */
    private function checklist(): array
    {
        $placeholderPrices = (int) Database::value("SELECT COUNT(*) FROM pricing_plans WHERE status = 'published' AND (price_label LIKE '%[%' OR features LIKE '%[%')");
        $placeholderStats = 0;
        for ($i = 1; $i <= 4; $i++) {
            $placeholderStats += is_placeholder(setting("stat{$i}_value")) ? 1 : 0;
        }
        $appUrl = (string) config('app.url', '');
        return [
            ['Phone number set (Settings or config.php)', !is_placeholder(setting('phone')), '/admin/settings'],
            ['Public email address set', !is_placeholder(setting('email')), '/admin/settings'],
            ['WhatsApp number set', whatsapp_href(setting('whatsapp')) !== '', '/admin/settings'],
            ['Lead notification email is valid', filter_var(setting('notify_email'), FILTER_VALIDATE_EMAIL) !== false, '/admin/settings'],
            ['Office address completed', !is_placeholder(setting('address_street')) && !is_placeholder(setting('address_postal')) && !is_placeholder(setting('address_locality')), '/admin/settings'],
            ['Opening hours set', !is_placeholder(setting('opening_hours')), '/admin/settings'],
            ['Homepage results use real numbers', $placeholderStats === 0, '/admin/settings'],
            ['Pricing plans have real prices and features', $placeholderPrices === 0, '/admin/plans'],
            ['Google Analytics ID added (optional)', ga_id() !== '', '/admin/settings'],
            ['Secret app.key changed in config.php', !Security::usingDefaultKey(), ''],
            ['Site runs on HTTPS (app.url starts with https://)', str_starts_with($appUrl, 'https://'), ''],
            ['SMTP email configured for reliable delivery (recommended)', config('mail.transport') === 'smtp', ''],
        ];
    }
}
