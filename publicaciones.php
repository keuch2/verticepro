<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pubs = PublicationRepo::all();
$types = SectionRepo::pubTypes();
$disc = SectionRepo::disciplines();
$page_title = 'Publicaciones Técnicas — Vértice Pro'; $page_active = 'publicaciones.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold">Publicaciones Técnicas</h1>
      <p class="text-gris-oscuro mt-2">Informes, guías, papers y normativa comentada.</p>
    </div>
  </section>

  <section id="filter-area" class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap gap-2 mb-3" data-filter-group="tipo">
      <button data-filter="todos" class="px-4 py-1.5 rounded-full border border-naranja bg-naranja text-white text-sm font-semibold">Todos</button>
      <?php foreach ($types as $t): ?>
        <button data-filter="<?= e($t['slug']) ?>" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold"><?= e($t['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-wrap gap-2" data-filter-group="disciplina">
      <button data-filter="todos" class="px-3 py-1 rounded border border-naranja bg-naranja text-white text-xs font-semibold">Todas</button>
      <?php foreach ($disc as $d): ?>
        <button data-filter="<?= e($d['slug']) ?>" class="px-3 py-1 rounded border border-gray-300 text-gris-oscuro text-xs font-semibold"><?= e($d['name']) ?></button>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 pb-14">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($pubs as $p): ?>
        <article data-card data-tipo="<?= e($p['type_slug']) ?>" data-disciplina="<?= e($p['discipline_slug']) ?>"
                 class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition" style="transition:opacity .2s, transform .2s;">
          <span class="text-xs px-2 py-0.5 rounded-full bg-gris-claro text-gris-oscuro font-semibold uppercase"><?= e($p['type_name']) ?></span>
          <h3 class="font-bold text-texto mt-3 leading-snug"><?= e($p['title']) ?></h3>
          <p class="text-xs text-gris-oscuro mt-2"><?= e($p['author_name']) ?></p>
          <div class="flex items-center justify-between mt-4 text-xs text-gris-oscuro">
            <span><?= e($p['discipline_name']) ?></span>
            <span><?= e(format_date($p['published_at'])) ?></span>
          </div>
          <a href="#" class="text-sm text-naranja font-semibold mt-3 inline-block hover:underline">Descargar →</a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
