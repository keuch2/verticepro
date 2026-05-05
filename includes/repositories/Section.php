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
    public static function cities(): array       { return DB::all('SELECT c.*, co.name country_name FROM cities c LEFT JOIN countries co ON co.id = c.country_id ORDER BY c.name'); }
    public static function countries(): array    { return DB::all('SELECT * FROM countries ORDER BY name'); }
    public static function profTypes(): array    { return DB::all('SELECT * FROM professional_types ORDER BY name'); }
    public static function pubTypes(): array     { return DB::all('SELECT * FROM publication_types ORDER BY name'); }
}
