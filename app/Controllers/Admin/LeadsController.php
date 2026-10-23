<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csv;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Models\Lead;

final class LeadsController extends AdminController
{
    public function index(): string
    {
        $q = Request::query('q');
        $status = Request::query('status');
        $type = Request::query('type');
        $page = max(1, (int) Request::query('page', '1'));
        [$where, $params] = Lead::filters($q, $status, $type);
        $total = (int) Database::value("SELECT COUNT(*) FROM leads WHERE $where", $params);
        $pager = new Paginator($total, 25, $page);
        $leads = Database::all("SELECT id, type, status, name, phone, email, website, business_type, created_at, is_sample
            FROM leads WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?", array_merge($params, [25, $pager->offset]));
        $counts = [];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM leads GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }
        return $this->admin('leads/index', compact('leads', 'pager', 'q', 'status', 'type', 'counts', 'total'), 'Leads');
    }

    public function show(string $id): string
    {
        $lead = $this->lead($id);
        return $this->admin('leads/show', ['lead' => $lead, 'notes' => Lead::notes((int) $lead['id'])], 'Lead: ' . $lead['name']);
    }

    public function status(string $id): void
    {
        $lead = $this->lead($id);
        $status = Request::input('status');
        if (!isset(Lead::STATUSES[$status])) {
            $this->error('Please choose a valid status.');
        } elseif ($status !== $lead['status']) {
            Database::update('leads', ['status' => $status], ['id' => (int) $lead['id']]);
            Database::insert('lead_notes', [
                'lead_id' => (int) $lead['id'],
                'admin_user_id' => Auth::id(),
                'note' => 'Status changed from ' . Lead::STATUSES[$lead['status']] . ' to ' . Lead::STATUSES[$status] . '.',
            ]);
            $this->success('Status updated to ' . Lead::STATUSES[$status] . '.');
        }
        redirect(url('/admin/leads/' . $lead['id']));
    }

    public function addNote(string $id): void
    {
        $lead = $this->lead($id);
        $note = Request::input('note');
        if ($note === '' || mb_strlen($note) > 5000) {
            $this->error('Please write a note (up to 5,000 characters).');
        } else {
            Database::insert('lead_notes', ['lead_id' => (int) $lead['id'], 'admin_user_id' => Auth::id(), 'note' => $note]);
            $this->success('Note added.');
        }
        redirect(url('/admin/leads/' . $lead['id']) . '#notes');
    }

    public function destroy(string $id): void
    {
        $lead = $this->lead($id);
        Database::delete('leads', ['id' => (int) $lead['id']]);
        $this->success('Lead deleted.');
        redirect(url('/admin/leads'));
    }

    public function export(): void
    {
        [$where, $params] = Lead::filters(Request::query('q'), Request::query('status'), Request::query('type'));
        $rows = Database::all("SELECT l.*, (SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i'), ': ', n.note)
                ORDER BY n.created_at SEPARATOR ' || ') FROM lead_notes n WHERE n.lead_id = l.id) AS notes
            FROM leads l WHERE $where ORDER BY l.created_at DESC, l.id DESC", $params);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                $row['id'], $row['created_at'], Lead::TYPES[$row['type']] ?? $row['type'], Lead::STATUSES[$row['status']] ?? $row['status'],
                $row['name'], $row['phone'], $row['email'], $row['website'], $row['business_type'], $row['message'],
                $row['notes'] ?? '', $row['source_page'],
            ];
        }
        Csv::download('noidarankers-leads-' . date('Y-m-d') . '.csv',
            ['ID', 'Date', 'Type', 'Status', 'Name', 'Phone', 'Email', 'Website', 'Business type', 'Message', 'Notes', 'Source page'], $out);
    }

    /** @return array<string, mixed> */
    private function lead(string $id): array
    {
        $lead = Lead::find($this->id($id));
        if ($lead === null) {
            throw new HttpException(404);
        }
        return $lead;
    }
}
