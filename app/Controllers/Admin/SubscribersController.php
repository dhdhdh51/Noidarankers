<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csv;
use App\Core\Database;
use App\Core\Paginator;
use App\Core\Request;

final class SubscribersController extends AdminController
{
    public function index(): string
    {
        $q = Request::query('q');
        $page = max(1, (int) Request::query('page', '1'));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where = 'email LIKE ?';
            $params[] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $total = (int) Database::value("SELECT COUNT(*) FROM subscribers WHERE $where", $params);
        $pager = new Paginator($total, 50, $page);
        $subscribers = Database::all("SELECT * FROM subscribers WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
            array_merge($params, [50, $pager->offset]));
        return $this->admin('subscribers', compact('subscribers', 'pager', 'q', 'total'), 'Newsletter subscribers');
    }

    public function destroy(string $id): void
    {
        Database::delete('subscribers', ['id' => $this->id($id)]);
        $this->success('Subscriber removed.');
        redirect(url('/admin/subscribers'));
    }

    public function export(): void
    {
        $rows = Database::all("SELECT email, status, created_at, source_page FROM subscribers ORDER BY created_at DESC");
        Csv::download('noidarankers-subscribers-' . date('Y-m-d') . '.csv', ['Email', 'Status', 'Subscribed on', 'Source page'],
            array_map(static fn (array $r): array => [$r['email'], $r['status'], $r['created_at'], $r['source_page']], $rows));
    }
}
