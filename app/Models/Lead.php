<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Lead
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'won' => 'Won', 'lost' => 'Lost'];
    public const TYPES = ['audit' => 'Free SEO audit', 'contact' => 'Contact form'];
    public const BUSINESS_TYPES = [
        'Clinic / Hospital',
        'Dentist',
        'Restaurant / Café',
        'Real estate',
        'Education / Coaching',
        'E-commerce / Online store',
        'Home services',
        'Salon / Spa / Gym',
        'Manufacturing / B2B',
        'Professional services (CA, lawyer, consultant)',
        'Other',
    ];

    /** @param array<string, string> $data */
    public static function create(array $data): int
    {
        return Database::insert('leads', $data);
    }

    /**
     * Admin search. @return array{0: string, 1: array<int, mixed>} WHERE clause and params
     */
    public static function filters(string $q, string $status, string $type): array
    {
        $where = ['1=1'];
        $params = [];
        if ($status !== '' && isset(self::STATUSES[$status])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($type !== '' && isset(self::TYPES[$type])) {
            $where[] = 'type = ?';
            $params[] = $type;
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR website LIKE ? OR business_type LIKE ? OR message LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        return [implode(' AND ', $where), $params];
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM leads WHERE id = ?', [$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function notes(int $leadId): array
    {
        return Database::all('SELECT n.*, a.name AS admin_name FROM lead_notes n LEFT JOIN admin_users a ON a.id = n.admin_user_id
            WHERE n.lead_id = ? ORDER BY n.created_at DESC, n.id DESC', [$leadId]);
    }
}
