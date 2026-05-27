<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$offers = JobOfferRepo::all();
$services = ServiceRepo::all();
$flash_ok  = flash('bolsa_ok');
$flash_err = flash('bolsa_err');

// Usuario logueado (profesional): cargar ids de ofertas que ya marcó como interés.
$current_user_ji = public_logged_in();
$my_interest_ids = [];
if ($current_user_ji && in_array($current_user_ji['role'], ['professional','admin','author'], true)) {
    $my_interest_ids = array_map('intval', array_column(
        DB::all('SELECT offer_id FROM job_interests WHERE user_id = ?', [(int)$current_user_ji['id']]),
        'offer_id'
    ));
}

$page_title = 'Bolsa de Trabajo — Vértice Pro'; $page_active = 'bolsa.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="bg-gris-claro py-12 px-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold">Bolsa de Trabajo</h1>
          <p class="text-gris-oscuro mt-2">Ofertas de empleo y servicios profesionales del sector en Paraguay.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a href="<?= e(u('/bolsa-publicar-oferta')) ?>" class="bg-naranja text-white font-semibold px-4 py-2 rounded hover:bg-orange-600 transition text-sm">Publicar oferta</a>
        </div>
      </div>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-8">
    <?php if ($flash_ok): ?><div class="bg-verde/10 border border-verde rounded p-4 mb-6 text-texto"><?= e($flash_ok) ?></div><?php endif; ?>
    <?php if ($flash_err): ?><div class="bg-red-50 border border-coral rounded p-4 mb-6 text-coral"><?= e($flash_err) ?></div><?php endif; ?>

    <div class="flex gap-2 mb-8" style="display:none;">
      <button id="btn-ofertas" class="bg-azul text-white px-5 py-2 rounded text-sm font-semibold">Ofertas de empleo</button>
      <button id="btn-servicios" class="bg-white border border-gray-300 text-gris-oscuro px-5 py-2 rounded text-sm font-semibold">Servicios profesionales</button>
    </div>

    <div id="ofertas-section" class="space-y-4">
      <?php foreach ($offers as $o): ?>
        <article class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition">
          <div class="flex flex-wrap gap-4 items-start">
            <?php if (!empty($o['flyer_image'])): ?>
              <a href="<?= e(img_url($o['flyer_image'])) ?>" target="_blank" rel="noopener" class="shrink-0">
                <img src="<?= e(img_url($o['flyer_image'])) ?>" alt="<?= e($o['title']) ?>" class="w-32 h-32 object-cover rounded border border-gray-200" />
              </a>
            <?php endif; ?>
            <div class="flex-1 min-w-[250px]">
              <h3 class="font-bold text-texto text-lg"><?= e($o['title']) ?></h3>
              <p class="text-sm text-gris-oscuro mt-0.5"><?= e($o['company_name']) ?> · <?= e($o['country_name']) ?></p>
              <p class="text-sm text-gris-oscuro mt-3 leading-relaxed"><?= e(mb_strimwidth($o['description'] ?? '', 0, 220, '…')) ?></p>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span class="text-xs px-2 py-0.5 rounded-full bg-orange-100 text-naranja font-semibold uppercase"><?= e($o['modality']) ?></span>
              <span class="text-xs text-gris-oscuro"><?= e($o['category']) ?></span>
              <?php if ($o['salary_min']): ?>
                <span class="text-xs text-gris-oscuro">Gs. <?= number_format($o['salary_min']) ?> – <?= number_format($o['salary_max']) ?></span>
              <?php endif; ?>
              <?php if ($current_user_ji): $marked = in_array((int)$o['id'], $my_interest_ids, true); ?>
                <form method="post" action="<?= e(u('/bolsa-interes.php')) ?>" class="mt-2">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                  <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>" />
                  <input type="hidden" name="action" value="<?= $marked ? 'unfavorite' : 'favorite' ?>" />
                  <button class="text-sm font-semibold transition <?= $marked ? 'text-coral hover:text-red-700' : 'text-naranja hover:underline' ?>" type="submit" title="<?= $marked ? 'Quitar de mis intereses' : 'Marcar como de mi interés' ?>">
                    <?= $marked ? '❤ En mis intereses' : '🤍 Me interesa →' ?>
                  </button>
                </form>
              <?php else: ?>
                <button type="button" class="text-sm text-naranja font-semibold hover:underline mt-2" data-interest-toggle="<?= (int)$o['id'] ?>">Me interesa →</button>
              <?php endif; ?>
            </div>
          </div>
          <form method="post" action="<?= e(u('/bolsa-interes.php')) ?>" class="hidden mt-4 pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-3 items-end" data-interest-form="<?= (int)$o['id'] ?>">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>" />
            <div>
              <label class="block text-xs font-semibold mb-1">Nombre completo *</label>
              <input name="name" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1">Email *</label>
              <input name="email" type="email" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
            </div>
            <div class="md:col-span-1">
              <button class="bg-naranja text-white font-semibold px-4 py-2 rounded hover:bg-orange-600 transition text-sm w-full" type="submit">Marcar mi interés</button>
            </div>
            <div class="md:col-span-3">
              <label class="block text-xs font-semibold mb-1">Mensaje (opcional)</label>
              <textarea name="message" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Cuéntale al ofertante por qué te interesa esta posición."></textarea>
            </div>
          </form>
        </article>
      <?php endforeach; ?>
    </div>

    <div id="servicios-section" class="space-y-4" style="display:none;">
      <?php foreach ($services as $s): ?>
        <article class="bg-white rounded-lg border border-gray-200 p-5 flex flex-wrap gap-4 items-start hover:shadow-md transition">
          <?php if (!empty($s['flyer_image'])): ?>
            <a href="<?= e(img_url($s['flyer_image'])) ?>" target="_blank" rel="noopener" class="shrink-0">
              <img src="<?= e(img_url($s['flyer_image'])) ?>" alt="<?= e($s['title']) ?>" class="w-32 h-32 object-cover rounded border border-gray-200" />
            </a>
          <?php endif; ?>
          <div class="flex-1 min-w-[250px]">
            <h3 class="font-bold text-texto text-lg"><?= e($s['title']) ?></h3>
            <p class="text-sm text-gris-oscuro mt-0.5">Ofrecido por <?= e($s['professional_name']) ?> · <?= e($s['country_name']) ?></p>
            <p class="text-sm text-gris-oscuro mt-3 leading-relaxed"><?= e($s['description']) ?></p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-azul font-semibold uppercase"><?= e($s['modality']) ?></span>
            <span class="text-xs text-gris-oscuro"><?= e($s['category']) ?></span>
            <a href="<?= e(u('/perfil/' . $s['professional_slug'])) ?>" class="text-sm text-naranja font-semibold hover:underline mt-2">Ver perfil →</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
