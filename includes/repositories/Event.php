<?php
require_once __DIR__ . '/../db.php';

class EventRepo {
    private const SQL = 'SELECT e.*, d.slug discipline_slug, d.name discipline_name
                         FROM events e
                         LEFT JOIN disciplines d ON d.id = e.discipline_id';

    public static function find(int $id): ?array {
        return DB::one(self::SQL . ' WHERE e.id = ?', [$id]);
    }

    public static function bySlug(string $slug): ?array {
        return DB::one(self::SQL . ' WHERE e.slug = ?', [$slug]);
    }

    public static function all(array $filters = []): array {
        $where = ['e.status = "published"']; $params = [];
        if (!empty($filters['year']))       { $where[] = 'YEAR(e.starts_at) = ?';  $params[] = (int)$filters['year']; }
        if (!empty($filters['month']))      { $where[] = 'MONTH(e.starts_at) = ?'; $params[] = (int)$filters['month']; }
        if (!empty($filters['discipline'])) { $where[] = 'd.slug = ?';             $params[] = $filters['discipline']; }
        return DB::all(self::SQL . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY e.starts_at ASC', $params);
    }

    public static function years(): array {
        $rows = DB::all('SELECT DISTINCT YEAR(starts_at) AS y FROM events WHERE status = "published" ORDER BY y DESC');
        return array_column($rows, 'y');
    }

    public static function adminAll(): array {
        return DB::all(self::SQL . ' ORDER BY e.starts_at DESC');
    }
}
