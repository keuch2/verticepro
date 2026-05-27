<?php
require_once __DIR__ . '/../db.php';

class SectionRepo {
    public static function all(): array {
        return DB::all('SELECT * FROM sections ORDER BY sort_order');
    }
    public static function bySlug(string $s): ?array {
        return DB::one('SELECT * FROM sections WHERE slug = ?', [$s]);
    }

    public static function disciplines(): array { return DB::all('SELECT * FROM disciplines ORDER BY id'); }
    public static function sectors(): array      { return DB::all('SELECT * FROM sectors ORDER BY id'); }
    public static function cities(): array       {
        return DB::all('SELECT c.*, co.name country_name, co.slug country_slug, co.sort_order country_sort,
                               d.slug department_slug, d.name department_name
                        FROM cities c
                        LEFT JOIN countries co ON co.id = c.country_id
                        LEFT JOIN departments d ON d.id = c.department_id
                        ORDER BY co.sort_order, c.name');
    }
    public static function countries(): array    { return DB::all('SELECT * FROM countries ORDER BY sort_order, name'); }
    public static function departments(?int $country_id = null): array {
        if ($country_id) {
            return DB::all('SELECT d.*, c.slug country_slug, c.name country_name FROM departments d JOIN countries c ON c.id = d.country_id WHERE d.country_id = ? ORDER BY d.sort_order, d.name', [$country_id]);
        }
        return DB::all('SELECT d.*, c.slug country_slug, c.name country_name FROM departments d JOIN countries c ON c.id = d.country_id ORDER BY c.sort_order, d.sort_order, d.name');
    }
    public static function profTypes(): array    { return DB::all('SELECT * FROM professional_types ORDER BY name'); }
    public static function pubTypes(): array     { return DB::all('SELECT * FROM publication_types ORDER BY name'); }
}
