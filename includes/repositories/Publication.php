<?php
require_once __DIR__ . '/../db.php';

class PublicationRepo {
    public static function all(): array {
        return DB::all('SELECT p.*, pt.slug type_slug, pt.name type_name, d.slug discipline_slug, d.name discipline_name
                        FROM publications p
                        LEFT JOIN publication_types pt ON pt.id = p.publication_type_id
                        LEFT JOIN disciplines d ON d.id = p.discipline_id
                        WHERE p.status = "published"
                        ORDER BY p.published_at DESC');
    }

    public static function bySlug(string $s): ?array {
        return DB::one('SELECT * FROM publications WHERE slug = ? AND status = "published"', [$s]);
    }
}

class ResourceRepo {
    public static function all(): array {
        return DB::all('SELECT * FROM resources WHERE status = "published" ORDER BY created_at DESC');
    }
}
