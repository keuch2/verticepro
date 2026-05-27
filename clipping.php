<?php
require_once __DIR__ . '/includes/bootstrap.php';

$disc = $_GET['discipline'] ?? '';
$items = NewsClippingRepo::published(['discipline' => $disc]);
$disciplines = SectionRepo::disciplines();

$page_title = 'Clipping de Noticias — Vértice Pro';
$page_active = 'clipping.php';
include __DIR__ . '/includes/header.php';
?>
<section class="bg-gris-claro py-12 px-6">
  <div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-extrabold">Clipping de Noticias</h1>
    <p class="text-gris-oscuro mt-2">Selección curada de noticias del sector publicadas en medios externos.</p>
  </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-8">
  <form method="get" class="flex flex-wrap gap-3 items-end bg-white border border-gray-200 rounded p-4 mb-6">
    <div>
      <label class="block text-xs font-semibold text-gris-oscuro mb-1">Disciplina</label>
      <select name="discipline" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todas</option>
        <?php foreach ($disciplines as $d): ?>
          <option value="<?= e($d['slug']) ?>" <?= $disc === $d['slug'] ? 'selected':'' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="bg-azul text-white font-semibold px-4 py-2 rounded text-sm">Filtrar</button>
    <a href="<?= e(u('/clipping')) ?>" class="text-sm text-naranja font-semibold hover:underline">Limpiar</a>
  </form>

  <?php if (!$items): ?>
    <div class="bg-gris-claro border border-gray-200 rounded p-8 text-center text-gris-oscuro">
      No hay noticias para los filtros seleccionados.
    </div>
  <?php else: ?>
    <ul class="space-y-4">
      <?php foreach ($items as $n): $color = section_color($n['discipline_slug'] ?? ''); ?>
        <li class="bg-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <p class="text-xs uppercase font-bold text-<?= e($color) ?>"><?= e($n['discipline_name'] ?? 'Noticia') ?> · <?= e($n['source_name']) ?></p>
              <h3 class="font-bold text-lg mt-1">
                <a href="<?= e($n['source_url']) ?>" target="_blank" rel="noopener" class="hover:underline"><?= e($n['title']) ?></a>
              </h3>
              <?php if ($n['summary']): ?>
                <p class="text-sm text-gris-oscuro mt-2 leading-relaxed"><?= e($n['summary']) ?></p>
              <?php endif; ?>
              <p class="text-xs text-gris-oscuro opacity-70 mt-2"><?= e(format_date($n['published_at'])) ?></p>
            </div>
            <a href="<?= e($n['source_url']) ?>" target="_blank" rel="noopener" class="shrink-0 text-sm text-naranja font-semibold hover:underline">Leer en la fuente →</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
