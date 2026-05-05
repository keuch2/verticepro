<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? 'calidad';
$section = SectionRepo::bySlug($slug);
if (!$section) { http_response_code(404); echo 'Sección no encontrada'; exit; }

$articles = ArticleRepo::bySection($slug, 24);
$color = $section['color_token'];

$page_title = $section['name'] . ' — Vértice Pro';
$page_active = $slug . '.php';
include __DIR__ . '/includes/header.php';
?>

  <section class="relative h-64 flex items-end" style="background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(0,0,0,0.25)), url('<?= e(img_url($section['hero_image'] ?? 'hero-ssl.jpg')) ?>'); background-size: cover; background-blend-mode: multiply;">
    <div class="max-w-7xl mx-auto px-6 pb-8 w-full text-white">
      <h1 class="text-4xl font-extrabold"><?= e($section['name']) ?></h1>
      <p class="text-gray-200 mt-2"><?= e($section['intro_text']) ?></p>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-12">
    <h2 class="text-xl font-bold mb-6">Artículos recientes</h2>
    <?php if (!$articles): ?>
      <p class="text-gris-oscuro">Próximamente nuevos contenidos para esta sección.</p>
    <?php endif; ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($articles as $a): ?>
      <article class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition">
        <?php if (!empty($a['thumb_image'])): ?>
          <img src="<?= e(img_url($a['thumb_image'])) ?>" alt="" class="w-full h-40 object-cover" />
        <?php endif; ?>
        <div class="p-5">
          <span class="text-xs font-semibold text-<?= e($color) ?>"><?= e($section['name']) ?></span>
          <h3 class="font-bold text-texto mt-2 leading-snug"><?= e($a['title']) ?></h3>
          <p class="text-sm text-gris-oscuro mt-2"><?= e($a['excerpt'] ?? '') ?></p>
          <div class="flex items-center justify-between mt-4 text-xs text-gris-oscuro">
            <span><?= e($a['author_name'] ?? 'Redacción VP') ?></span>
            <span><?= e(format_date($a['published_at'])) ?></span>
          </div>
          <a href="<?= e(article_url($a)) ?>" class="text-sm text-naranja font-semibold mt-3 inline-block hover:underline">Leer más →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
