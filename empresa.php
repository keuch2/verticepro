<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$emp = $slug ? CompanyRepo::bySlug($slug) : null;
if (!$emp) { http_response_code(404); echo 'Empresa no encontrada'; exit; }

$cid = (int)$emp['id'];
$offers = DB::all(
    'SELECT * FROM job_offers WHERE company_id = ? AND status = "published" ORDER BY published_at DESC, created_at DESC',
    [$cid]
);
$team = DB::all(
    'SELECT p.*, c.name AS city_name, t.name AS type_name
     FROM professionals p
     LEFT JOIN cities c ON c.id = p.city_id
     LEFT JOIN professional_types t ON t.id = p.type_id
     WHERE p.company_id = ? AND p.status = "active"
     ORDER BY p.featured DESC, p.name',
    [$cid]
);

$show_email   = !empty($emp['email'])   && (int)($emp['visibility_email']   ?? 1);
$show_website = !empty($emp['website']) && (int)($emp['visibility_website'] ?? 1);
$show_phone   = !empty($emp['phone'])   && (int)($emp['visibility_phone']   ?? 0);
$wa_number    = $show_phone ? preg_replace('/[^0-9]/', '', $emp['phone']) : '';

$page_title = $emp['name'] . ' — Vértice Pro';
$page_active = 'empresa.php';
include __DIR__ . '/includes/header.php';
?>
<section class="bg-gris-claro py-10 px-6">
  <div class="max-w-6xl mx-auto flex flex-wrap gap-6 items-start">
    <div class="w-24 h-24 rounded bg-white flex items-center justify-center text-gris-oscuro text-2xl font-extrabold shrink-0 border border-gray-200">
      <?php if (!empty($emp['logo_image'])): ?>
        <img src="<?= e(img_url($emp['logo_image'])) ?>" alt="<?= e($emp['name']) ?>" class="max-w-full max-h-full object-contain" />
      <?php else: ?>
        <?= e(mb_substr($emp['name'], 0, 2)) ?>
      <?php endif; ?>
    </div>
    <div class="flex-1 min-w-[250px]">
      <div class="flex items-center gap-2">
        <h1 class="text-3xl font-extrabold"><?= e($emp['name']) ?></h1>
        <?php if ($emp['verified']): ?><span class="text-xs bg-azul text-white px-2 py-0.5 rounded-full">Verificada</span><?php endif; ?>
      </div>
      <p class="text-gris-oscuro mt-1">
        <?= e($emp['sector_name'] ?? '') ?>
        <?= !empty($emp['city_name']) ? ' · ' . e($emp['city_name']) : '' ?>
        <?= !empty($emp['country_name']) ? ', ' . e($emp['country_name']) : '' ?>
      </p>
      <?php if (!empty($emp['founded_year'])): ?><p class="text-xs text-gris-oscuro mt-1 opacity-80">Fundada en <?= (int)$emp['founded_year'] ?><?= !empty($emp['size']) ? ' · ' . e($emp['size']) . ' empleados' : '' ?></p><?php endif; ?>
    </div>
  </div>
</section>

<div class="max-w-6xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
  <div class="lg:col-span-2 space-y-8">
    <?php if (!empty($emp['description'])): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="font-bold text-lg mb-3">Sobre la empresa</h2>
        <p class="text-gris-oscuro leading-relaxed whitespace-pre-line"><?= e($emp['description']) ?></p>
      </section>
    <?php endif; ?>

    <section class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-lg">Ofertas de empleo activas</h2>
        <?php if ($offers): ?><span class="text-sm text-gris-oscuro"><?= count($offers) ?></span><?php endif; ?>
      </div>
      <?php if (!$offers): ?>
        <p class="text-sm text-gris-oscuro">Esta empresa no tiene ofertas activas en este momento.</p>
      <?php else: ?>
        <ul class="space-y-3">
          <?php foreach ($offers as $o): ?>
            <li class="border-l-2 border-naranja pl-4">
              <h3 class="font-bold text-texto"><?= e($o['title']) ?></h3>
              <p class="text-xs text-gris-oscuro mt-0.5"><?= e(ucfirst($o['modality'] ?? '')) ?><?= !empty($o['category']) ? ' · ' . e($o['category']) : '' ?> · publicada <?= e(format_date($o['published_at'] ?? $o['created_at'])) ?></p>
              <?php if (!empty($o['description'])): ?><p class="text-sm text-gris-oscuro mt-2"><?= e(mb_strimwidth($o['description'], 0, 200, '…')) ?></p><?php endif; ?>
              <a href="<?= e(u('/bolsa')) ?>#oferta-<?= (int)$o['id'] ?>" class="text-sm text-naranja font-semibold hover:underline mt-2 inline-block">Ver en bolsa →</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <?php if ($team): ?>
      <section class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-bold text-lg">Equipo profesional</h2>
          <span class="text-sm text-gris-oscuro"><?= count($team) ?></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <?php foreach ($team as $tp):
            $disc = ProfessionalRepo::primaryDisciplineSlug((int)$tp['id']);
            $pc = section_color($disc ?: 'seguridad'); ?>
            <a href="<?= e(profile_url($tp)) ?>" class="flex items-center gap-3 border border-gray-200 rounded p-3 hover:border-naranja hover:shadow-sm transition">
              <div class="w-10 h-10 rounded-full bg-<?= e($pc) ?> flex items-center justify-center text-white font-bold text-sm shrink-0">
                <?= e(mb_substr($tp['name'], 0, 1)) ?><?= e(mb_substr(strstr($tp['name'], ' ') ?: ' ', 1, 1)) ?>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm truncate"><?= e($tp['name']) ?></p>
                <p class="text-xs text-gris-oscuro truncate"><?= e($tp['title'] ?? '') ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <aside class="space-y-6">
    <?php if ($show_email || $show_website || $show_phone): ?>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-bold mb-3">Contacto</h3>
        <?php if ($show_email): ?><p class="text-sm mb-1">✉️ <a href="mailto:<?= e($emp['email']) ?>" class="text-azul"><?= e($emp['email']) ?></a></p><?php endif; ?>
        <?php if ($show_phone): ?>
          <p class="text-sm mb-1">📞 <a href="tel:<?= e($emp['phone']) ?>" class="text-azul"><?= e($emp['phone']) ?></a><?php if ($wa_number): ?> · <a href="https://wa.me/<?= e($wa_number) ?>" target="_blank" rel="noopener" class="text-verde">WhatsApp</a><?php endif; ?></p>
        <?php endif; ?>
        <?php if ($show_website): ?><p class="text-sm">🌐 <a href="<?= e($emp['website']) ?>" target="_blank" rel="noopener" class="text-azul"><?= e(preg_replace('#^https?://#', '', $emp['website'])) ?></a></p><?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="font-bold mb-3">Datos</h3>
      <dl class="space-y-2 text-sm">
        <?php if (!empty($emp['sector_name'])): ?>
          <div class="flex justify-between"><dt class="text-gris-oscuro">Sector</dt><dd class="font-semibold"><?= e($emp['sector_name']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($emp['size'])): ?>
          <div class="flex justify-between"><dt class="text-gris-oscuro">Tamaño</dt><dd class="font-semibold"><?= e($emp['size']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($emp['founded_year'])): ?>
          <div class="flex justify-between"><dt class="text-gris-oscuro">Fundación</dt><dd class="font-semibold"><?= (int)$emp['founded_year'] ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($emp['country_name'])): ?>
          <div class="flex justify-between"><dt class="text-gris-oscuro">País</dt><dd class="font-semibold"><?= e($emp['country_name']) ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
