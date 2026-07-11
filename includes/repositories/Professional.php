<?php
require_once __DIR__ . '/../db.php';

class ProfessionalRepo {
    private const BASE_SELECT = 'SELECT p.*, c.name city_name, c.slug city_slug,
                                        d.slug department_slug, d.name department_name,
                                        co.name country_name, co.slug country_slug,
                                        t.slug type_slug, t.name type_name
                                 FROM professionals p
                                 LEFT JOIN cities c ON c.id = p.city_id
                                 LEFT JOIN departments d ON d.id = c.department_id
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

    /** Devuelve todos los tipos del profesional (M:N). */
    public static function types(int $id): array {
        return DB::all(
            'SELECT t.id, t.slug, t.name, l.is_primary
             FROM professional_type_links l
             JOIN professional_types t ON t.id = l.type_id
             WHERE l.professional_id = ?
             ORDER BY l.is_primary DESC, t.name',
            [$id]
        );
    }

    public static function typeIds(int $id): array {
        return array_map('intval', array_column(
            DB::all('SELECT type_id FROM professional_type_links WHERE professional_id = ?', [$id]),
            'type_id'
        ));
    }

    /** Reemplaza la lista de tipos del profesional. El primero pasa a ser primario. */
    public static function setTypes(int $id, array $type_ids): void {
        // DELETE + re-INSERT atómico (anidable: comparte la transacción del caller si existe).
        DB::transaction(function () use ($id, $type_ids) {
            DB::run('DELETE FROM professional_type_links WHERE professional_id = ?', [$id]);
            $first = true;
            foreach (array_unique(array_map('intval', $type_ids)) as $tid) {
                if ($tid <= 0) continue;
                try {
                    DB::insert('professional_type_links', [
                        'professional_id' => $id,
                        'type_id'         => $tid,
                        'is_primary'      => $first ? 1 : 0,
                    ]);
                    if ($first) {
                        DB::update('professionals', ['type_id' => $tid], ['id' => $id]);
                        $first = false;
                    }
                } catch (\Throwable $e) {
                    // id de tipo inválido: se registra en vez de tragarse en silencio.
                    error_log('[ProfessionalRepo::setTypes] type_id inválido ' . $tid . ' para profesional ' . $id . ': ' . $e->getMessage());
                }
            }
            if ($first) {
                // Sin tipos válidos, limpiar el cache
                DB::update('professionals', ['type_id' => null], ['id' => $id]);
            }
        });
    }
}
