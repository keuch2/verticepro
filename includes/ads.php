<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Helpers de publicidad — banners por slot, gestionados desde admin.
 *
 * Slots disponibles:
 *   - header_top:       franja superior del header.
 *   - sidebar:          aside derecho en secciones editoriales.
 *   - between_articles: entre cards de artículos en home/sección.
 *   - footer:           sobre el footer global.
 *   - home_hero:        debajo del hero del home.
 */
class Ads {
    public static function forSlot(string $slot, int $limit = 1): array {
        return DB::all(
            'SELECT * FROM ads
             WHERE slot = ?
               AND status = "active"
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at   IS NULL OR ends_at   >  NOW())
             ORDER BY sort_order ASC, RAND()
             LIMIT ' . (int)$limit,
            [$slot]
        );
    }

    public static function all(): array {
        return DB::all('SELECT * FROM ads ORDER BY slot, sort_order, id DESC');
    }

    public static function find(int $id): ?array {
        return DB::one('SELECT * FROM ads WHERE id = ?', [$id]);
    }
}

/**
 * Renderiza el HTML del slot. Llamar desde templates: <?= ad_slot('header_top') ?>
 * Si no hay ads activos, retorna string vacío.
 */
function ad_slot(string $slot, array $opts = []): string {
    $ads = Ads::forSlot($slot, $opts['limit'] ?? 1);
    if (!$ads) return '';
    $out = '';
    $wrap = $opts['wrap_class'] ?? 'my-4';
    foreach ($ads as $a) {
        $inner = '';
        if (!empty($a['image_path'])) {
            $img = '<img src="' . e(img_url($a['image_path'])) . '" alt="' . e($a['alt'] ?? $a['name']) . '" class="block max-w-full h-auto mx-auto" />';
            $inner = !empty($a['target_url'])
                ? '<a href="' . e($a['target_url']) . '" target="_blank" rel="noopener sponsored">' . $img . '</a>'
                : $img;
        } elseif (!empty($a['html_content'])) {
            // HTML libre — confianza del admin.
            $inner = $a['html_content'];
        }
        if ($inner !== '') {
            $out .= '<div class="ad-slot ad-' . e($slot) . ' ' . e($wrap) . '" data-ad-id="' . (int)$a['id'] . '"><span class="text-[10px] uppercase tracking-wide text-gris-oscuro opacity-50 block mb-1">Publicidad</span>' . $inner . '</div>';
        }
    }
    return $out;
}
