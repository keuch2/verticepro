<?php
require_once __DIR__ . '/../db.php';

class ProfessionalRepo {
    private const BASE_SELECT = 'SELECT p.*, c.name city_name, c.slug city_slug, co.name country_name, co.slug country_slug, t.slug type_slug, t.name type_name
                                 FROM professionals p
                                 LEFT JOIN cities c ON c.id = p.city_id
                                 LEFT JOIN countries co ON co.id = c.country_id
                                 LEFT JOIN professional_types t ON t.id = p.type_id';

    public static function find(int $id): ?array {
        return DB::one(self::BASE_SELECT . ' WHERE p.id = ?', [$id]);
    }

    public static function bySlug(string $slug): ?array {
        return DB::one(self::BASE_SELECT . ' WHERE p.slug = ? AND p.status = "active"', [$slug]);
    }

    public static function featured(int $limit = 4): array {
        return DB::all(self::BASE_SELECT . ' WHERE p.status = "active" AND p.featured = 1 ORDER BY p.id LIMIT ' . (int)$limit);
    }

    public static function all(array $filters = []): array {
        $where = ['p.status = "active"']; $params = [];
        if (!empty($filters['city']))      { $where[] = 'c.slug = ?'; $params[] = $filters['city']; }
        if (!empty($filters['type']))      { $where[] = 't.slug = ?'; $params[] = $filters['type']; }
        $sql = self::BASE_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.featured DESC, p.id';
        return DB::all($sql, $params);
    }

    public static function disciplines(int $id): array {
        return DB::all('SELECT d.slug, d.name FROM professional_disciplines pd JOIN disciplines d ON d.id = pd.discipline_id WHERE pd.professional_id = ?', [$id]);
    }
    public static function primaryDisciplineSlug(int $id): string {
        $row = DB::one('SELECT d.slug FROM professional_disciplines pd JOIN disciplines d ON d.id = pd.discipline_id WHERE pd.professional_id = ? LIMIT 1', [$id]);
        return $row['slug'] ?? '';
    }
    public static function specialties(int $id): array {
        return array_column(DB::all('SELECT specialty FROM professional_specialties WHERE professional_id = ?', [$id]), 'specialty');
    }
    public static function formation(int $id): array {
        return DB::all('SELECT * FROM professional_formation WHERE professional_id = ? ORDER BY sort_order', [$id]);
    }
    public static function experience(int $id): array {
        return DB::all('SELECT * FROM professional_experience WHERE professional_id = ? ORDER BY sort_order', [$id]);
    }
    public static function services(int $id): array {
        return DB::all('SELECT * FROM professional_services WHERE professional_id = ? ORDER BY sort_order', [$id]);
    }
}
