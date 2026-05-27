<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pros = ProfessionalRepo::all();
$cities = SectionRepo::cities();
$departments = SectionRepo::departments();
$types = SectionRepo::profTypes();

$page_title = 'Directorio de Profesionales — Vértice Pro';
$page_active = 'directorio.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-texto">Directorio de Profesionales</h1>
        <p class="text-gris-oscuro mt-2">Encuentra consultores, auditores y expertos verificados en Paraguay.</p>
      </div>
      <a href="<?= e(u('/registro')) ?>" class="bg-naranja text-white font-semibold px-5 py-2.5 rounded hover:bg-orange-600 transition">Únete a la red</a>
    </div>
  </section>

  <section id="filter-area" class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-wrap gap-2 mb-4" data-filter-group="disciplina">
      <button data-filter="todos" class="px-4 py-1.5 rounded-full border border-naranja bg-naranja text-white text-sm font-semibold">Todos</button>
      <button data-filter="calidad" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold">Calidad</button>
      <button data-filter="seguridad" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold">Seguridad</button>
      <button data-filter="medio-ambiente" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold">Medio Ambiente</button>
      <button data-filter="salud" class="px-4 py-1.5 rounded-full border border-gray-300 text-gris-oscuro text-sm font-semibold">Salud</button>
    </div>
    <div class="flex flex-wrap gap-3 items-center">
      <input type="search" data-filter-axis="search" placeholder="Buscar por nombre, especialidad…" class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64" />
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
      <select data-filter-axis="tipo" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
        <option value="">Todos los tipos</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t['slug']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
      </select>
      <button id="clear-filters" class="text-sm text-naranja font-semibold hover:underline">Limpiar filtros</button>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 pb-14">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($pros as $p):
        $disc = ProfessionalRepo::primaryDisciplineSlug((int)$p['id']);
        $pc = section_color($disc ?: 'seguridad');
        $specialties = implode(' ', ProfessionalRepo::specialties((int)$p['id']));
        $type_names  = implode(' ', array_column(ProfessionalRepo::types((int)$p['id']), 'name'));
        $search_blob = strtolower(trim(
          ($p['name'] ?? '') . ' ' . ($p['title'] ?? '') . ' ' . ($p['bio'] ?? '') . ' ' .
          ($p['city_name'] ?? '') . ' ' . ($p['type_name'] ?? '') . ' ' . $type_names . ' ' . $specialties
        )); ?>
        <article data-card
                 data-disciplina="<?= e($disc) ?>"
                 data-departamento="<?= e($p['department_slug'] ?? '') ?>"
                 data-ciudad="<?= e($p['city_slug'] ?? '') ?>"
                 data-tipo="<?= e($p['type_slug'] ?? '') ?>"
                 data-search="<?= e($search_blob) ?>"
                 class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition" style="transition:opacity .2s, transform .2s;">
          <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-full bg-<?= e($pc) ?> flex items-center justify-center text-white font-bold">
              <?= e(mb_substr($p['name'], 0, 1)) ?><?= e(mb_substr(strstr($p['name'], ' ') ?: ' ', 1, 1)) ?>
            </div>
            <div class="flex-1">
              <h3 class="font-bold text-texto leading-snug"><?= e($p['name']) ?></h3>
              <p class="text-sm text-gris-oscuro mt-0.5"><?= e($p['title']) ?></p>
              <p class="text-xs text-gris-oscuro opacity-70 mt-1"><?= e(trim(($p['city_name'] ?? '') . ($p['country_name'] ? ', ' . $p['country_name'] : ''), ', ')) ?></p>
            </div>
          </div>
          <p class="text-sm text-gris-oscuro mt-4"><?= e($p['bio'] ? mb_strimwidth($p['bio'], 0, 140, '…') : '') ?></p>
          <div class="flex items-center justify-between mt-4">
            <?php $all_types = ProfessionalRepo::types((int)$p['id']);
                  $type_label = $all_types
                    ? implode(' · ', array_column($all_types, 'name'))
                    : ($p['type_name'] ?? ''); ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-<?= e($pc) ?>/10 text-<?= e($pc) ?> font-semibold"><?= e($type_label) ?></span>
            <a href="<?= e(profile_url($p)) ?>" class="text-sm text-naranja font-semibold hover:underline">Ver perfil →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
