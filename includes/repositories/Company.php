<?php
require_once __DIR__ . '/../db.php';

class CompanyRepo {
    private const SQL = 'SELECT c.*, s.slug sector_slug, s.name sector_name,
                                co.slug country_slug, co.name country_name,
                                ci.slug city_slug, ci.name city_name,
                                d.slug department_slug, d.name department_name
                         FROM companies c
                         LEFT JOIN sectors s ON s.id = c.sector_id
                         LEFT JOIN countries co ON co.id = c.country_id
                         LEFT JOIN cities ci ON ci.id = c.city_id
                         LEFT JOIN departments d ON d.id = ci.department_id';

    public static function find(int $id): ?array   { return DB::one(self::SQL . ' WHERE c.id = ?', [$id]); }
    public static function bySlug(string $s): ?array { return DB::one(self::SQL . ' WHERE c.slug = ? AND c.status = "active"', [$s]); }
    public static function all(): array              { return DB::all(self::SQL . ' WHERE c.status = "active" ORDER BY c.name'); }

    public static function offersCount(int $id): int {
        $r = DB::one('SELECT COUNT(*) c FROM job_offers WHERE company_id = ? AND status = "published"', [$id]);
        return (int)($r['c'] ?? 0);
    }

    public static function sectors(int $id): array {
        return DB::all(
            'SELECT s.id, s.slug, s.name, l.is_primary
             FROM company_sector_links l JOIN sectors s ON s.id = l.sector_id
             WHERE l.company_id = ?
             ORDER BY l.is_primary DESC, s.name',
            [$id]
        );
    }

    public static function sectorIds(int $id): array {
        return array_map('intval', array_column(
            DB::all('SELECT sector_id FROM company_sector_links WHERE company_id = ?', [$id]),
            'sector_id'
        ));
    }

    public static function setSectors(int $id, array $sector_ids): void {
        DB::run('DELETE FROM company_sector_links WHERE company_id = ?', [$id]);
        $first = true;
        foreach (array_unique(array_map('intval', $sector_ids)) as $sid) {
            if ($sid <= 0) continue;
            try {
                DB::insert('company_sector_links', [
                    'company_id' => $id,
                    'sector_id'  => $sid,
                    'is_primary' => $first ? 1 : 0,
                ]);
                if ($first) {
                    DB::update('companies', ['sector_id' => $sid], ['id' => $id]);
                    $first = false;
                }
            } catch (\Throwable $e) {}
        }
        if ($first) DB::update('companies', ['sector_id' => null], ['id' => $id]);
    }
}
