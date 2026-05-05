<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$article = $slug ? ArticleRepo::bySlug($slug) : ArticleRepo::featured();
if (!$article) { http_response_code(404); echo 'Artículo no encontrado'; exit; }

$tags = ArticleRepo::tags((int)$article['id']);
$related = ArticleRepo::related((int)$article['id'], 3);
$also = ArticleRepo::recent(3, (int)$article['id']);

$page_title = $article['title'] . ' — Vértice Pro';
$page_description = $article['excerpt'] ?? $article['subtitle'] ?? '';
$page_active = 'articulo.php';
$color = section_color($article['section_slug'] ?? 'seguridad');
$author_initials = mb_strtoupper(mb_substr($article['author_name'] ?? 'VP', 0, 1) . mb_substr(strstr($article['author_name'] ?? ' ', ' ') ?: ' ', 1, 1));

include __DIR__ . '/includes/header.php';
?>

  <div class="bg-white border-b border-gray-200 py-10 px-6">
    <div class="max-w-4xl mx-auto">
      <nav class="text-sm text-gris-oscuro mb-4">
        <a href="<?= e(u('/')) ?>" class="hover:text-naranja transition">Inicio</a>
        <span class="mx-2 text-gray-300">›</span>
        <?php if (!empty($article['section_slug'])): ?>
          <a href="<?= e(section_url($article['section_slug'])) ?>" class="hover:text-naranja transition"><?= e($article['section_name']) ?></a>
          <span class="mx-2 text-gray-300">›</span>
        <?php endif; ?>
        <span class="text-texto"><?= e($article['title']) ?></span>
      </nav>
      <?php if (!empty($article['section_name'])): ?>
      <span class="text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wide bg-<?= e($color) ?> text-white"><?= e($article['section_name']) ?></span>
      <?php endif; ?>
      <h1 class="text-4xl font-extrabold text-texto leading-tight mt-4 max-w-3xl"><?= e($article['title']) ?></h1>
      <?php if (!empty($article['subtitle'])): ?>
        <p class="text-lg text-gris-oscuro mt-3 max-w-3xl"><?= e($article['subtitle']) ?></p>
      <?php endif; ?>
      <div class="flex flex-wrap gap-4 items-center text-sm text-gris-oscuro mt-5">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-<?= e($color) ?> flex items-center justify-center text-white text-xs font-bold"><?= e($author_initials) ?></div>
          <span class="font-semibold text-texto"><?= e($article['author_name'] ?? 'Redacción Vértice Pro') ?></span>
        </div>
        <div class="flex items-center gap-1">
          <span><?= e(format_date($article['published_at'])) ?></span>
        </div>
        <div class="flex items-center gap-1">
          <span><?= (int)$article['read_time_min'] ?> min de lectura</span>
        </div>
      </div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-6 py-12">

    <?php if (!empty($article['hero_image'])): ?>
      <img src="<?= e(img_url($article['hero_image'])) ?>" alt="<?= e($article['title']) ?>" class="w-full h-80 object-cover rounded-lg mb-10" />
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
      <article class="lg:col-span-3 prose-article">
        <?= $article['body'] ?: '<p>' . e($article['excerpt']) . '</p>' ?>

        <?php if ($tags): ?>
        <div class="border-t border-gray-200 pt-6 mt-10 flex flex-wrap gap-2">
          <span class="text-xs text-gris-oscuro font-medium">Etiquetas:</span>
          <?php foreach ($tags as $t): ?>
            <a href="#" class="text-xs px-3 py-1 rounded-full border border-gray-200 text-gris-oscuro hover:border-naranja hover:text-naranja transition"><?= e($t) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg border border-gray-200 p-5 mt-8 flex items-center gap-4">
          <div class="w-14 h-14 rounded-full bg-<?= e($color) ?> flex items-center justify-center text-white font-bold text-lg shrink-0"><?= e($author_initials) ?></div>
          <div>
            <p class="font-bold text-texto"><?= e($article['author_name'] ?? 'Redacción Vértice Pro') ?></p>
            <p class="text-sm text-gris-oscuro mt-0.5">Equipo editorial especializado en seguridad laboral, sostenibilidad y normativa preventiva.</p>
          </div>
        </div>
      </article>

      <aside class="lg:col-span-1">
        <div class="sticky top-24 space-y-6">
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="font-bold text-sm mb-4 uppercase tracking-wide text-xs text-gris-oscuro">Artículos relacionados</h3>
            <div class="space-y-4">
              <?php foreach ($related as $r): ?>
                <a href="<?= e(article_url($r)) ?>" class="flex gap-3 hover:bg-gris-claro rounded p-1 transition">
                  <?php if (!empty($r['thumb_image'])): ?>
                    <img src="<?= e(img_url($r['thumb_image'])) ?>" alt="" class="w-14 h-14 rounded object-cover shrink-0" />
                  <?php endif; ?>
                  <p class="text-xs font-semibold text-texto leading-snug"><?= e($r['title']) ?></p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="bg-azul rounded-lg p-5 text-white text-center">
            <p class="font-bold text-sm">¿Eres experto en <?= e($article['section_name'] ?? 'este tema') ?>?</p>
            <p class="text-blue-200 text-xs mt-1 mb-3">Únete a la Red de Profesionales</p>
            <a href="<?= e(u('/red')) ?>" class="block bg-white text-azul font-bold text-xs py-2 rounded hover:bg-blue-50 transition">Ver la Red →</a>
          </div>
        </div>
      </aside>
    </div>

    <?php if ($also): ?>
    <section class="mt-16 pt-10 border-t border-gray-200">
      <h2 class="text-2xl font-extrabold text-texto mb-6">También te puede interesar</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($also as $a): ?>
          <article class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition">
            <?php if (!empty($a['thumb_image'])): ?>
              <img src="<?= e(img_url($a['thumb_image'])) ?>" alt="" class="w-full h-44 object-cover" />
            <?php endif; ?>
            <div class="p-5">
              <span class="text-xs font-semibold text-<?= e(section_color($a['section_slug'] ?? 'seguridad')) ?>"><?= e($a['section_name'] ?? '') ?></span>
              <h3 class="font-bold text-texto mt-2 leading-snug"><?= e($a['title']) ?></h3>
              <a href="<?= e(article_url($a)) ?>" class="text-sm text-naranja font-semibold mt-3 inline-block hover:underline">Leer más →</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>

<style>.prose-article p{font-size:1rem;line-height:1.75;margin-bottom:1.25rem;color:#1A1A1A}.prose-article h2{font-size:1.5rem;font-weight:700;margin:2rem 0 .75rem}.prose-article blockquote{border-left:4px solid #F58220;padding-left:1.5rem;font-style:italic;color:#54636F;margin:2rem 0;font-size:1.125rem}</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
