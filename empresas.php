<?php
require_once __DIR__ . '/includes/bootstrap.php';
$companies = CompanyRepo::all();
$sectors = SectionRepo::sectors();
$countries = SectionRepo::countries();
$departments = SectionRepo::departments();
$cities = SectionRepo::cities();
$page_title = 'Empresas — Vértice Pro'; $page_active = 'empresas.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold">Empresas</h1>
          <p class="text-gris-oscuro mt-2">Consultoras, auditoras y proveedores del sector en Paraguay.</p>
        </div>
        <a href="<?= e(u('/registro-empresa')) ?>" class="bg-naranja text-white font-semibold px-5 py-2.5 rounded hover:bg-orange-600 transition">Registra tu empresa</a>
      </div>
    </div>
  </section>

  <section id="filter-area" class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap gap-2 mb-4" data-filter-group="sector">
      <button data-filter="todos" class="px-4 py-1.5 rounded-full border border-naranja bg-naranja text-white text-sm font-semibold">Todos</button>
      <?php foreach ($sectors as $s): ?>
        <button data-filter="<?= e($s['slug']) ?>" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold"><?= e($s['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-wrap gap-3 items-center">
      <input type="search" data-filter-axis="search" placeholder="Buscar por nombre, descripción…" class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64" />
      <select data-filter-axis="departamento" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos los departamentos</option>
        <?php foreach ($departments as $d): ?><option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
      </select>
      <select data-filter-axis="ciudad" data-depends-on="departamento" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todas las ciudades</option>
        <?php foreach ($cities as $c): ?>
          <option value="<?= e($c['slug']) ?>" data-departamento="<?= e($c['department_slug'] ?? '') ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select data-filter-axis="pais" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos los países</option>
        <?php foreach ($countries as $c): ?><option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <button id="clear-filters" class="text-sm text-naranja font-semibold hover:underline">Limpiar filtros</button>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 pb-14">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($companies as $c):
        $offers = CompanyRepo::offersCount((int)$c['id']);
        $search_blob = strtolower(trim(
          ($c['name'] ?? '') . ' ' . ($c['description'] ?? '') . ' ' .
          ($c['sector_name'] ?? '') . ' ' . ($c['city_name'] ?? '') . ' ' . ($c['department_name'] ?? '')
        ));
        $loc = trim(($c['city_name'] ?? '') . ($c['city_name'] && $c['country_name'] ? ', ' : '') . ($c['country_name'] ?? ''));
      ?>
        <article data-card
                 data-sector="<?= e($c['sector_slug']) ?>"
                 data-pais="<?= e($c['country_slug']) ?>"
                 data-departamento="<?= e($c['department_slug'] ?? '') ?>"
                 data-ciudad="<?= e($c['city_slug'] ?? '') ?>"
                 data-search="<?= e($search_blob) ?>"
                 class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition" style="transition:opacity .2s, transform .2s;">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 rounded bg-gris-claro flex items-center justify-center text-gris-oscuro font-bold">
              <?= e(mb_substr($c['name'], 0, 2)) ?>
            </div>
            <div>
              <h3 class="font-bold text-texto leading-snug"><?= e($c['name']) ?></h3>
              <p class="text-xs text-gris-oscuro"><?= e($loc ?: ($c['country_name'] ?? '')) ?> · <?= e($c['sector_name']) ?></p>
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
