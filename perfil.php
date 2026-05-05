<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? 'ana-garcia';
$p = ProfessionalRepo::bySlug($slug);
if (!$p) { http_response_code(404); echo 'Perfil no encontrado'; exit; }

$disc = ProfessionalRepo::disciplines((int)$p['id']);
$spec = ProfessionalRepo::specialties((int)$p['id']);
$form = ProfessionalRepo::formation((int)$p['id']);
$exp  = ProfessionalRepo::experience((int)$p['id']);
$serv = ProfessionalRepo::services((int)$p['id']);
$primary = $disc[0]['slug'] ?? 'seguridad';
$color = section_color($primary);

$page_title = $p['name'] . ' — Vértice Pro'; $page_active = 'perfil.php';
include __DIR__ . '/includes/header.php';
?>

  <section class="bg-gris-claro py-10 px-6">
    <div class="max-w-6xl mx-auto flex flex-wrap gap-6 items-start">
      <div class="w-24 h-24 rounded-full bg-<?= e($color) ?> flex items-center justify-center text-white text-3xl font-bold shrink-0">
        <?= e(mb_substr($p['name'], 0, 1)) ?><?= e(mb_substr(strstr($p['name'], ' ') ?: ' ', 1, 1)) ?>
      </div>
      <div class="flex-1 min-w-[250px]">
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-extrabold"><?= e($p['name']) ?></h1>
          <?php if ($p['verified']): ?><span class="text-xs bg-azul text-white px-2 py-0.5 rounded-full">Verificado</span><?php endif; ?>
        </div>
        <p class="text-gris-oscuro mt-1"><?= e($p['title']) ?></p>
        <p class="text-sm text-gris-oscuro opacity-70 mt-1"><?= e(trim(($p['city_name'] ?? '') . ($p['country_name'] ? ', ' . $p['country_name'] : ''), ', ')) ?></p>
        <div class="flex flex-wrap gap-2 mt-3">
          <?php foreach ($disc as $d): ?>
            <span class="text-xs px-2 py-0.5 rounded-full bg-<?= e(section_color($d['slug'])) ?>/15 text-<?= e(section_color($d['slug'])) ?> font-semibold"><?= e($d['name']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if ($p['available']): ?>
        <span class="text-sm bg-verde text-white px-3 py-1 rounded font-semibold">Disponible</span>
      <?php endif; ?>
    </div>
  </section>

  <div class="max-w-6xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2 space-y-8">
      <?php if ($p['bio']): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-3">Sobre mí</h2>
        <p class="text-gris-oscuro leading-relaxed"><?= e($p['bio']) ?></p>
      </section>
      <?php endif; ?>

      <?php if ($spec): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-3">Especialidades</h2>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($spec as $s): ?>
            <span class="text-sm px-3 py-1 rounded-full border border-gray-200 text-gris-oscuro"><?= e($s) ?></span>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($form): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-4">Formación</h2>
        <ul class="space-y-4">
          <?php foreach ($form as $f): ?>
            <li class="border-l-2 border-<?= e($color) ?> pl-4">
              <p class="font-semibold"><?= e($f['degree']) ?></p>
              <p class="text-sm text-gris-oscuro"><?= e($f['institution']) ?> · <?= e($f['date_from']) ?> – <?= e($f['date_to']) ?></p>
              <?php if ($f['details']): ?><p class="text-sm text-gris-oscuro mt-1"><?= e($f['details']) ?></p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($exp): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-4">Experiencia</h2>
        <ul class="space-y-4">
          <?php foreach ($exp as $x): ?>
            <li class="border-l-2 border-azul pl-4">
              <p class="font-semibold"><?= e($x['job_title']) ?></p>
              <p class="text-sm text-gris-oscuro"><?= e($x['company']) ?> · <?= e($x['date_from']) ?> – <?= e($x['date_to']) ?></p>
              <?php if ($x['description']): ?><p class="text-sm text-gris-oscuro mt-1"><?= e($x['description']) ?></p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($serv): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-4">Servicios ofrecidos</h2>
        <ul class="space-y-3">
          <?php foreach ($serv as $s): ?>
            <li><p class="font-semibold"><?= e($s['title']) ?></p><p class="text-sm text-gris-oscuro"><?= e($s['description']) ?></p></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    </div>

    <aside class="space-y-6">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-bold mb-3">Contacto</h3>
        <?php if ($p['email']): ?><p class="text-sm mb-1"><a href="mailto:<?= e($p['email']) ?>" class="text-azul"><?= e($p['email']) ?></a></p><?php endif; ?>
        <?php if ($p['linkedin']): ?><p class="text-sm mb-1"><a href="https://<?= e($p['linkedin']) ?>" class="text-azul">LinkedIn</a></p><?php endif; ?>
        <?php if ($p['website']): ?><p class="text-sm"><a href="https://<?= e($p['website']) ?>" class="text-azul"><?= e($p['website']) ?></a></p><?php endif; ?>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-bold mb-3">Actividad</h3>
        <div class="grid grid-cols-2 gap-3 text-center">
          <div><div class="text-2xl font-extrabold text-<?= e($color) ?>"><?= e($p['stats_years_exp']) ?>+</div><div class="text-xs text-gris-oscuro">Años exp.</div></div>
          <div><div class="text-2xl font-extrabold text-<?= e($color) ?>"><?= (int)$p['stats_articles'] ?></div><div class="text-xs text-gris-oscuro">Artículos</div></div>
          <div><div class="text-2xl font-extrabold text-<?= e($color) ?>"><?= (int)$p['stats_connections'] ?></div><div class="text-xs text-gris-oscuro">Conexiones</div></div>
          <div><div class="text-2xl font-extrabold text-<?= e($color) ?>"><?= (int)$p['stats_projects'] ?></div><div class="text-xs text-gris-oscuro">Proyectos</div></div>
        </div>
      </div>
    </aside>
  </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
