<?php
require_once __DIR__ . '/includes/bootstrap.php';
$companies = CompanyRepo::all();
$sectors = SectionRepo::sectors();
$countries = SectionRepo::countries();
$page_title = 'Empresas — Vértice Pro'; $page_active = 'empresas.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold">Empresas</h1>
      <p class="text-gris-oscuro mt-2">Consultoras, auditoras y proveedores del sector.</p>
    </div>
  </section>

  <section id="filter-area" class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap gap-2 mb-4" data-filter-group="sector">
      <button data-filter="todos" class="px-4 py-1.5 rounded-full border border-naranja bg-naranja text-white text-sm font-semibold">Todos</button>
      <?php foreach ($sectors as $s): ?>
        <button data-filter="<?= e($s['slug']) ?>" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold"><?= e($s['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="flex gap-3 items-center">
      <select data-filter-axis="pais" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos los países</option>
        <?php foreach ($countries as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button id="clear-filters" class="text-sm text-naranja font-semibold hover:underline">Limpiar filtros</button>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 pb-14">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($companies as $c): $offers = CompanyRepo::offersCount((int)$c['id']); ?>
        <article data-card data-sector="<?= e($c['sector_slug']) ?>" data-pais="<?= e($c['country_slug']) ?>"
                 class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition" style="transition:opacity .2s, transform .2s;">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded bg-gris-claro flex items-center justify-center text-gris-oscuro font-bold">
              <?= e(mb_substr($c['name'], 0, 2)) ?>
            </div>
            <div>
              <h3 class="font-bold text-texto leading-snug"><?= e($c['name']) ?></h3>
              <p class="text-xs text-gris-oscuro"><?= e($c['country_name']) ?> · <?= e($c['sector_name']) ?></p>
            </div>
          </div>
          <p class="text-sm text-gris-oscuro leading-relaxed"><?= e($c['description']) ?></p>
          <div class="flex items-center justify-between mt-4 text-xs text-gris-oscuro">
            <span>Fundada <?= (int)$c['founded_year'] ?></span>
            <span><?= $offers ?> oferta<?= $offers === 1 ? '' : 's' ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
