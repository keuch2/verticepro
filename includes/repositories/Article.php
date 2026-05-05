<?php
require_once __DIR__ . '/../db.php';

class ArticleRepo {
    public static function find(int $id): ?array {
        return DB::one('SELECT a.*, d.name discipline_name, d.slug discipline_slug, s.name section_name, s.color_token section_color, u.name author_name
                        FROM articles a
                        LEFT JOIN disciplines d ON d.id = a.discipline_id
                        LEFT JOIN sections s ON s.slug = a.section_slug
                        LEFT JOIN users u ON u.id = a.author_id
                        WHERE a.id = ?', [$id]);
    }

    public static function bySlug(string $slug): ?array {
        return DB::one('SELECT a.*, d.name discipline_name, d.slug discipline_slug, s.name section_name, s.color_token section_color, u.name author_name
                        FROM articles a
                        LEFT JOIN disciplines d ON d.id = a.discipline_id
                        LEFT JOIN sections s ON s.slug = a.section_slug
                        LEFT JOIN users u ON u.id = a.author_id
                        WHERE a.slug = ? AND a.status = "published"', [$slug]);
    }

    public static function featured(): ?array {
        return DB::one('SELECT a.*, d.name discipline_name, d.slug discipline_slug, s.name section_name, s.color_token section_color, u.name author_name
                        FROM articles a
                        LEFT JOIN disciplines d ON d.id = a.discipline_id
                        LEFT JOIN sections s ON s.slug = a.section_slug
                        LEFT JOIN users u ON u.id = a.author_id
                        WHERE a.status = "published" AND a.featured = 1
                        ORDER BY a.published_at DESC LIMIT 1');
    }

    public static function recent(int $limit = 3, ?int $exclude_id = null): array {
        $sql = 'SELECT a.*, d.name discipline_name, d.slug discipline_slug, s.name section_name, s.color_token section_color, u.name author_name
                FROM articles a
                LEFT JOIN disciplines d ON d.id = a.discipline_id
                LEFT JOIN sections s ON s.slug = a.section_slug
                LEFT JOIN users u ON u.id = a.author_id
                WHERE a.status = "published" ' . ($exclude_id ? ' AND a.id <> ? ' : '') . '
                ORDER BY a.published_at DESC LIMIT ' . (int)$limit;
        return DB::all($sql, $exclude_id ? [$exclude_id] : []);
    }

    public static function bySection(string $section_slug, int $limit = 12): array {
        return DB::all('SELECT a.*, u.name author_name
                        FROM articles a
                        LEFT JOIN users u ON u.id = a.author_id
                        WHERE a.status = "published" AND a.section_slug = ?
                        ORDER BY a.published_at DESC LIMIT ' . (int)$limit, [$section_slug]);
    }

    public static function tags(int $article_id): array {
        return array_column(DB::all('SELECT tag FROM article_tags WHERE article_id = ?', [$article_id]), 'tag');
    }

    public static function related(int $article_id, int $limit = 3): array {
        $rows = DB::all('SELECT a.id, a.slug, a.title, a.thumb_image, a.published_at, a.read_time_min, s.name section_name
                         FROM article_related r
                         JOIN articles a ON a.id = r.related_article_id
                         LEFT JOIN sections s ON s.slug = a.section_slug
                         WHERE r.article_id = ? AND a.status = "published"
                         ORDER BY r.sort_order LIMIT ' . (int)$limit, [$article_id]);
        if (count($rows) < $limit) {
            // Fallback: más recientes de la misma sección
            $needed = $limit - count($rows);
            $art = self::find($article_id);
            if ($art && $art['section_slug']) {
                $extra = DB::all('SELECT a.id, a.slug, a.title, a.thumb_image, a.published_at, a.read_time_min, s.name section_name
                                  FROM articles a LEFT JOIN sections s ON s.slug = a.section_slug
                                  WHERE a.status = "published" AND a.section_slug = ? AND a.id <> ?
                                  ORDER BY a.published_at DESC LIMIT ' . (int)$needed,
                                  [$art['section_slug'], $article_id]);
                $rows = array_merge($rows, $extra);
            }
        }
        return $rows;
    }

    public static function all(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where = ['1=1']; $params = [];
        if (!empty($filters['status']))  { $where[] = 'a.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['section'])) { $where[] = 'a.section_slug = ?'; $params[] = $filters['section']; }
        if (!empty($filters['q']))       { $where[] = '(a.title LIKE ? OR a.excerpt LIKE ?)'; $p = '%'.$filters['q'].'%'; $params[] = $p; $params[] = $p; }
        $sql = 'SELECT a.id, a.slug, a.title, a.status, a.published_at, a.featured, s.name section_name
                FROM articles a LEFT JOIN sections s ON s.slug = a.section_slug
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.published_at DESC, a.id DESC
                LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return DB::all($sql, $params);
    }
}
