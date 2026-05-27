<?php
require_once __DIR__ . '/../db.php';

class NewsClippingRepo {
    private const SQL = 'SELECT nc.*, d.slug discipline_slug, d.name discipline_name, s.name section_name
                         FROM news_clippings nc
                         LEFT JOIN disciplines d ON d.id = nc.discipline_id
                         LEFT JOIN sections s ON s.slug = nc.section_slug';

    public static function find(int $id): ?array {
        return DB::one(self::SQL . ' WHERE nc.id = ?', [$id]);
    }

    public static function published(array $filters = []): array {
        $where = ['nc.status = "published"']; $params = [];
        if (!empty($filters['discipline'])) { $where[] = 'd.slug = ?';   $params[] = $filters['discipline']; }
        if (!empty($filters['section']))    { $where[] = 's.slug = ?';   $params[] = $filters['section']; }
        return DB::all(self::SQL . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY nc.published_at DESC, nc.id DESC', $params);
    }

    public static function adminAll(): array {
        return DB::all(self::SQL . ' ORDER BY nc.published_at DESC, nc.id DESC');
    }
}
