<?php
require_once __DIR__ . '/../db.php';

class CompanyRepo {
    private const SQL = 'SELECT c.*, s.slug sector_slug, s.name sector_name, co.slug country_slug, co.name country_name
                         FROM companies c
                         LEFT JOIN sectors s ON s.id = c.sector_id
                         LEFT JOIN countries co ON co.id = c.country_id';

    public static function find(int $id): ?array   { return DB::one(self::SQL . ' WHERE c.id = ?', [$id]); }
    public static function bySlug(string $s): ?array { return DB::one(self::SQL . ' WHERE c.slug = ? AND c.status = "active"', [$s]); }
    public static function all(): array              { return DB::all(self::SQL . ' WHERE c.status = "active" ORDER BY c.name'); }

    public static function offersCount(int $id): int {
        $r = DB::one('SELECT COUNT(*) c FROM job_offers WHERE company_id = ? AND status = "published"', [$id]);
        return (int)($r['c'] ?? 0);
    }
}
