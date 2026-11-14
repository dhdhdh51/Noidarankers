<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Csv;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\MapsLeads;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Settings;
use App\Models\MapLead;

/**
 * Admin > Lead Finder. Search Google Maps (via the official Places API) for
 * local businesses and save them as prospects to contact.
 */
final class LeadFinderController extends AdminController
{
    public function index(): string
    {
        $q = Request::query('q');
        $status = Request::query('status');
        $page = max(1, (int) Request::query('page', '1'));
        [$where, $params] = MapLead::filters($q, $status);
        $total = (int) Database::value("SELECT COUNT(*) FROM map_leads WHERE $where", $params);
        $pager = new Paginator($total, 25, $page);
        $prospects = Database::all(
            "SELECT * FROM map_leads WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
            array_merge($params, [25, $pager->offset])
        );
        $counts = [];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM map_leads GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['n'];
        }
        return $this->admin('lead-finder/index', [
            'prospects' => $prospects,
            'pager' => $pager,
            'q' => $q,
            'status' => $status,
            'counts' => $counts,
            'total' => $total,
            'configured' => MapsLeads::configured(),
        ], 'Lead Finder');
    }

    public function search(): void
    {
        @set_time_limit(120);
        if (!MapsLeads::configured()) {
            $this->error('Add your Google Places API key first.');
            redirect(url('/admin/lead-finder/settings'));
        }
        $query = Request::input('query');
        if ($query === '') {
            $this->error('Enter what to search for, e.g. "dentists in Noida".');
            redirect(url('/admin/lead-finder'));
        }
        $max = (int) Request::input('max', '20');
        $minRating = (float) Request::input('min_rating', '0');
        $rank = Request::input('rank') === 'DISTANCE' ? 'DISTANCE' : 'RELEVANCE';
        $region = Request::input('region') !== '' ? Request::input('region') : 'IN';

        try {
            $results = MapsLeads::search($query, $region, $max, $minRating, $rank);
        } catch (\Throwable $e) {
            $this->error('Search failed: ' . $e->getMessage());
            redirect(url('/admin/lead-finder'));
            return;
        }

        if ($results === []) {
            $this->error('No businesses found for "' . $query . '". Try a broader or different search.');
            redirect(url('/admin/lead-finder'));
            return;
        }

        $saved = MapLead::saveMany($results, $query);
        $msg = 'Found ' . count($results) . ' — added ' . $saved['added'] . ' new prospect' . ($saved['added'] === 1 ? '' : 's');
        if ($saved['duplicates'] > 0) {
            $msg .= ', ' . $saved['duplicates'] . ' already saved';
        }
        $this->success($msg . '.');
        redirect(url('/admin/lead-finder'));
    }

    public function status(string $id): void
    {
        $lead = $this->prospect($id);
        $status = Request::input('status');
        if (!isset(MapLead::STATUSES[$status])) {
            $this->error('Please choose a valid status.');
        } else {
            Database::update('map_leads', ['status' => $status], ['id' => (int) $lead['id']]);
            $this->success('Status updated for ' . $lead['name'] . '.');
        }
        redirect(url('/admin/lead-finder'));
    }

    public function destroy(string $id): void
    {
        $lead = $this->prospect($id);
        Database::delete('map_leads', ['id' => (int) $lead['id']]);
        $this->success('Prospect deleted.');
        redirect(url('/admin/lead-finder'));
    }

    public function export(): void
    {
        [$where, $params] = MapLead::filters(Request::query('q'), Request::query('status'));
        $rows = Database::all("SELECT * FROM map_leads WHERE $where ORDER BY created_at DESC, id DESC", $params);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                $row['name'], $row['category'], $row['phone'], $row['website'], $row['address'],
                $row['rating'] ?? '', $row['reviews'], MapLead::STATUSES[$row['status']] ?? $row['status'],
                $row['maps_url'], $row['search_query'], $row['created_at'],
            ];
        }
        Csv::download(
            'noidarankers-prospects-' . date('Y-m-d') . '.csv',
            ['Business', 'Category', 'Phone', 'Website', 'Address', 'Rating', 'Reviews', 'Status', 'Maps URL', 'Search', 'Found on'],
            $out
        );
    }

    public function settings(): string
    {
        return $this->admin('lead-finder/settings', [
            'enabled' => MapsLeads::config()['enabled'],
            'hasKey' => setting('maps_api_key') !== '',
        ], 'Lead Finder settings');
    }

    public function saveSettings(): void
    {
        $values = ['maps_enabled' => Request::input('maps_enabled') === '1' ? '1' : '0'];
        $key = trim(Request::raw('maps_api_key'));
        if ($key !== '') {
            $values['maps_api_key'] = Crypto::encrypt($key);
        }
        Settings::save($values);
        $this->success('Lead Finder settings saved.');
        redirect(url('/admin/lead-finder/settings'));
    }

    public function test(): string
    {
        header('Content-Type: application/json');
        $key = trim(Request::raw('maps_api_key'));
        if ($key !== '') {
            MapsLeads::useRuntimeConfig(['api_key' => $key]);
        }
        return (string) json_encode(MapsLeads::test());
    }

    /** @return array<string,mixed> */
    private function prospect(string $id): array
    {
        $lead = MapLead::find($this->id($id));
        if ($lead === null) {
            throw new HttpException(404);
        }
        return $lead;
    }
}
