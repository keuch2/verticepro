<?php
require_once __DIR__ . '/includes/bootstrap.php';

$featured = ArticleRepo::featured();
$recent = ArticleRepo::recent(3, $featured ? (int)$featured['id'] : null);
$pros = ProfessionalRepo::featured(4);
$sections = array_filter(SectionRepo::all(), fn($s) => in_array($s['slug'], ['calidad','seguridad','medioambiente','salud'], true));

$page_title = 'Vértice Pro — Hub Editorial';
$page_active = 'index.php';
include __DIR__ . '/includes/header.php';
?>

    <?php if ($featured): $fc = section_color($featured['section_slug']); ?>
    <section class="min-h-[520px] flex items-end relative overflow-hidden">
      <div class="absolute inset-0" style="background-image: url('<?= e(img_url($featured['hero_image'] ?? 'hero-ssl.jpg')) ?>'); background-size: cover; background-position: center;"></div>
      <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/50 to-transparent"></div>
      <div class="relative max-w-7xl mx-auto px-6 pb-16 pt-24 w-full">
        <div class="max-w-2xl">
          <?php if (!empty($featured['section_name'])): ?>
            <span class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide bg-<?= e($fc) ?> text-white inline-block mb-4"><?= e($featured['section_name']) ?></span>
          <?php endif; ?>
          <h1 class="text-4xl font-extrabold text-white leading-tight"><?= e($featured['title']) ?></h1>
          <p class="text-gray-300 mt-4 text-base leading-relaxed max-w-xl"><?= e($featured['excerpt'] ?? $featured['subtitle'] ?? '') ?></p>
          <div class="flex items-center gap-3 mt-4 text-sm text-gray-400">
            <span><?= e($featured['author_name'] ?? 'Redacción Vértice Pro') ?></span>
            <span>·</span>
            <span><?= e(format_date($featured['published_at'])) ?></span>
          </div>
          <a href="<?= e(article_url($featured)) ?>" class="inline-block mt-6 border border-white text-white px-5 py-2 rounded hover:bg-white hover:text-texto transition duration-200 text-sm font-semibold">Leer artículo →</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <section class="max-w-7xl mx-auto px-6 py-14">
      <div class="flex justify-between items-baseline mb-8">
        <h2 class="text-2xl font-extrabold text-texto">Artículos recientes</h2>
        <a href="<?= e(u('/seccion/seguridad')) ?>" class="text-sm text-naranja font-semibold hover:underline">Ver todos →</a>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($recent as $a): $ac = section_color($a['section_slug'] ?? 'seguridad'); ?>
        <article class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition duration-200">
          <?php if (!empty($a['thumb_image'])): ?>
            <img src="<?= e(img_url($a['thumb_image'])) ?>" class="w-full h-40 object-cover" alt="<?= e($a['title']) ?>" />
          <?php endif; ?>
          <div class="p-5">
            <span class="text-xs font-semibold text-<?= e($ac) ?>"><?= e($a['section_name'] ?? '') ?></span>
            <h3 class="font-bold text-texto mt-2 leading-snug"><?= e($a['title']) ?></h3>
            <p class="text-sm text-gris-oscuro mt-2 leading-relaxed"><?= e($a['excerpt'] ?? '') ?></p>
            <a href="<?= e(article_url($a)) ?>" class="text-sm text-naranja font-semibold mt-3 inline-block hover:underline">Leer más →</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-gris-claro py-14">
      <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-baseline mb-8">
          <h2 class="text-2xl font-extrabold text-texto">Red de Profesionales</h2>
          <a href="<?= e(u('/directorio')) ?>" class="text-sm text-naranja font-semibold hover:underline">Ver directorio →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <?php foreach ($pros as $p): $pc = section_color(ProfessionalRepo::primaryDisciplineSlug((int)$p['id'])); ?>
          <a href="<?= e(profile_url($p)) ?>" class="bg-white rounded-lg border border-gray-200 p-5 text-center hover:shadow-md transition">
            <div class="w-16 h-16 rounded-full bg-<?= e($pc) ?> mx-auto flex items-center justify-center text-white font-bold text-xl">
              <?= e(mb_substr($p['name'], 0, 1)) ?><?= e(mb_substr(strstr($p['name'], ' ') ?: ' ', 1, 1)) ?>
            </div>
            <h3 class="font-bold text-texto mt-3"><?= e($p['name']) ?></h3>
            <p class="text-xs text-gris-oscuro mt-1 leading-snug"><?= e($p['title']) ?></p>
            <p class="text-xs text-gris-oscuro mt-2 opacity-70"><?= e(trim(($p['city_name'] ?? '') . ($p['country_name'] ? ', ' . $p['country_name'] : ''), ', ')) ?></p>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 py-14">
      <h2 class="text-2xl font-extrabold text-texto mb-8">Explora por temática</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($sections as $s): $sc = $s['color_token']; ?>
        <a href="<?= e(section_url($s['slug'])) ?>" class="block p-6 rounded-lg bg-white border-t-4 border-<?= e($sc) ?> hover:shadow-md transition">
          <h3 class="font-bold text-texto"><?= e($s['name']) ?></h3>
          <p class="text-sm text-gris-oscuro mt-1"><?= e($s['intro_text']) ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
