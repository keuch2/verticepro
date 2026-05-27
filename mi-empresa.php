<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
require_once __DIR__ . '/includes/image.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$u = require_company();
$c = current_company();

if (!$c) {
    $page_title = 'Mi empresa — Vértice Pro';
    include __DIR__ . '/includes/header.php';
    echo '<section class="max-w-3xl mx-auto px-6 py-16"><p>No encontramos el perfil de empresa vinculado a tu cuenta. Contacta a soporte.</p></section>';
    include __DIR__ . '/includes/footer.php';
    return;
}

$tab = $_GET['tab'] ?? 'datos';
$valid_tabs = ['datos', 'ofertas', 'interesados', 'password'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'datos';

$ok = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'save_datos':
                $data = [
                    'name'         => trim($_POST['name'] ?? ''),
                    'description'  => trim($_POST['description'] ?? '') ?: null,
                    // sector_id se sincroniza desde sectors[] más abajo
                    'country_id'   => !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null,
                    'city_id'      => !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null,
                    'founded_year' => !empty($_POST['founded_year']) ? (int)$_POST['founded_year'] : null,
                    'website'      => trim($_POST['website'] ?? '') ?: null,
                    'phone'        => trim($_POST['phone'] ?? '') ?: null,
                    'visibility_email'    => !empty($_POST['visibility_email'])    ? 1 : 0,
                    'visibility_website'  => !empty($_POST['visibility_website'])  ? 1 : 0,
                    'visibility_phone'    => !empty($_POST['visibility_phone'])    ? 1 : 0,
                    'notifications_opt_in'=> !empty($_POST['notifications_opt_in']) ? 1 : 0,
                ];
                if ($data['name'] === '') throw new RuntimeException('El nombre es obligatorio.');
                if ($data['website'] && !preg_match('#^https?://#i', $data['website'])) throw new RuntimeException('Website debe empezar con http(s)://');

                if (!empty($_FILES['logo']['name'])) {
                    $rel = upload_image($_FILES['logo'], 'companies', $c['slug']);
                    if ($rel) $data['logo_image'] = $rel;
                }
                DB::update('companies', $data, ['id' => (int)$c['id']]);
                // Sectores (M:N) — sincroniza también sector_id como "principal"
                if (isset($_POST['sectors'])) {
                    CompanyRepo::setSectors((int)$c['id'], (array)$_POST['sectors']);
                }
                if (!empty($c['user_id'])) DB::update('users', ['name' => $data['name']], ['id' => (int)$c['user_id']]);
                $ok = 'Datos actualizados.';
                $tab = 'datos';
                break;

            case 'publish_offer':
                $title       = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category    = trim($_POST['category'] ?? '') ?: null;
                $modality    = $_POST['modality'] ?? null;
                $country_id  = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
                $salary_min  = !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : null;
                $salary_max  = !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : null;

                if ($title === '')       throw new RuntimeException('Indica un título.');
                if ($description === '') throw new RuntimeException('Describe la oferta.');
                if (!$modality)          throw new RuntimeException('Selecciona modalidad.');

                $base = slugify($title) ?: 'oferta';
                $slug = $base; $i = 2;
                while (DB::one('SELECT id FROM job_offers WHERE slug = ? LIMIT 1', [$slug])) {
                    $slug = $base . '-' . $i++;
                    if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
                }
                $flyer = null;
                if (!empty($_FILES['flyer']['name'])) {
                    $rel = upload_image($_FILES['flyer'], 'flyers', $slug);
                    if ($rel) $flyer = $rel;
                }
                DB::insert('job_offers', [
                    'company_id'  => (int)$c['id'],
                    'slug'        => $slug,
                    'title'       => $title,
                    'description' => $description,
                    'flyer_image' => $flyer,
                    'category'    => $category,
                    'modality'    => $modality,
                    'country_id'  => $country_id,
                    'salary_min'  => $salary_min,
                    'salary_max'  => $salary_max,
                    'status'      => 'published',
                    'published_at'=> date('Y-m-d H:i:s'),
                ]);
                $ok = 'Oferta publicada.';
                $tab = 'ofertas';
                break;

            case 'close_offer':
                $oid = (int)($_POST['offer_id'] ?? 0);
                $owned = DB::one('SELECT id FROM job_offers WHERE id = ? AND company_id = ?', [$oid, (int)$c['id']]);
                if ($owned) DB::update('job_offers', ['status' => 'closed'], ['id' => $oid]);
                $ok = 'Oferta cerrada.';
                $tab = 'ofertas';
                break;

            case 'reopen_offer':
                $oid = (int)($_POST['offer_id'] ?? 0);
                $owned = DB::one('SELECT id FROM job_offers WHERE id = ? AND company_id = ?', [$oid, (int)$c['id']]);
                if ($owned) DB::update('job_offers', ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')], ['id' => $oid]);
                $ok = 'Oferta reactivada.';
                $tab = 'ofertas';
                break;

            case 'save_password':
                $cur = $_POST['current_password'] ?? '';
                $pw1 = $_POST['new_password'] ?? '';
                $pw2 = $_POST['new_password_confirm'] ?? '';
                $userRow = DB::one('SELECT password_hash FROM users WHERE id = ?', [(int)$u['id']]);
                if (!$userRow || !password_verify($cur, $userRow['password_hash'])) throw new RuntimeException('Contraseña actual incorrecta.');
                if (strlen($pw1) < 8) throw new RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
                if ($pw1 !== $pw2)    throw new RuntimeException('Las contraseñas no coinciden.');
                DB::update('users', ['password_hash' => password_hash($pw1, PASSWORD_DEFAULT)], ['id' => (int)$u['id']]);
                $ok = 'Contraseña actualizada.';
                $tab = 'password';
                break;
        }
        $c = current_company();
    } catch (\Throwable $e) {
        $err = $e->getMessage();
    }
}

$sectors   = SectionRepo::sectors();
$countries = SectionRepo::countries();
$cities    = SectionRepo::cities();
$my_offers = DB::all('SELECT * FROM job_offers WHERE company_id = ? ORDER BY created_at DESC', [(int)$c['id']]);
$my_interests = DB::all(
    'SELECT i.*, o.title AS offer_title FROM job_interests i JOIN job_offers o ON o.id = i.offer_id WHERE o.company_id = ? ORDER BY i.created_at DESC',
    [(int)$c['id']]
);

$page_title = 'Mi empresa — Vértice Pro';
$page_active = 'mi-empresa.php';
include __DIR__ . '/includes/header.php';

function tab_link_emp(string $t, string $current, string $label, ?int $count = null): string {
    $cls = $t === $current ? 'border-naranja text-naranja' : 'border-transparent text-gris-oscuro hover:text-naranja';
    $url = e(u('/mi-empresa?tab=' . $t));
    $badge = $count !== null ? '<span class="ml-1.5 text-xs bg-gray-200 text-gris-oscuro rounded-full px-1.5">' . $count . '</span>' : '';
    return '<a href="' . $url . '" class="px-1 py-3 border-b-2 ' . $cls . ' font-semibold text-sm transition">' . e($label) . $badge . '</a>';
}
?>
<section class="bg-gris-claro py-8 px-6">
  <div class="max-w-5xl mx-auto flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-2xl font-extrabold">Mi empresa</h1>
      <p class="text-gris-oscuro text-sm mt-1"><?= e($c['name']) ?> · <a href="<?= e(u('/empresa/' . $c['slug'])) ?>" target="_blank" class="text-azul hover:underline">Ver perfil público →</a></p>
    </div>
    <div class="text-sm">
      <?php if ($c['status'] === 'pending'): ?>
        <span class="bg-orange-100 text-naranja px-3 py-1 rounded-full font-semibold">Pendiente de aprobación</span>
      <?php elseif ($c['status'] === 'active'): ?>
        <span class="bg-verde/10 text-verde px-3 py-1 rounded-full font-semibold">Empresa activa</span>
      <?php else: ?>
        <span class="bg-red-50 text-coral px-3 py-1 rounded-full font-semibold"><?= e(ucfirst($c['status'])) ?></span>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-6">
  <nav class="flex flex-wrap gap-5 border-b border-gray-200">
    <?= tab_link_emp('datos',       $tab, 'Datos de la empresa') ?>
    <?= tab_link_emp('ofertas',     $tab, 'Mis ofertas', count($my_offers)) ?>
    <?= tab_link_emp('interesados', $tab, 'Interesados', count($my_interests)) ?>
    <?= tab_link_emp('password',    $tab, 'Contraseña') ?>
  </nav>

  <?php if ($ok): ?><div class="mt-5 bg-verde/10 border border-verde rounded p-3 text-sm text-texto"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="mt-5 bg-red-50 border border-coral rounded p-3 text-sm text-coral"><?= e($err) ?></div><?php endif; ?>
</section>

<section class="max-w-5xl mx-auto px-6 py-8">
<?php if ($tab === 'datos'): ?>
  <form method="post" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_datos" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-semibold mb-1">Nombre *</label>
        <input name="name" required value="<?= e($c['name']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input value="<?= e($c['email']) ?>" disabled class="w-full border border-gray-200 bg-gray-50 rounded px-3 py-2 text-gris-oscuro" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Descripción</label>
      <textarea name="description" rows="5" maxlength="2000" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($c['description'] ?? '') ?></textarea>
    </div>

    <div>
      <?php $current_sectors = CompanyRepo::sectorIds((int)$c['id']); ?>
      <label class="block text-sm font-semibold mb-2">Sectores</label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($sectors as $s): $checked = in_array((int)$s['id'], $current_sectors, true); ?>
          <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
            <input type="checkbox" name="sectors[]" value="<?= (int)$s['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
            <span><?= e($s['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div>
        <label class="block text-sm font-semibold mb-1">País</label>
        <select id="me-country" name="country_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
          <option value="">—</option>
          <?php foreach ($countries as $cc): ?><option value="<?= (int)$cc['id'] ?>" <?= (int)($c['country_id'] ?? 0) === (int)$cc['id'] ? 'selected' : '' ?>><?= e($cc['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Departamento</label>
        <select id="me-dept" name="department_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
          <option value="">— Selecciona —</option>
          <?php $departments_me = SectionRepo::departments(); foreach ($departments_me as $d): ?>
            <option value="<?= (int)$d['id'] ?>" data-country="<?= (int)$d['country_id'] ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Ciudad</label>
        <select id="me-city" name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
          <option value="">— Selecciona —</option>
          <?php foreach ($cities as $ci): ?><option value="<?= (int)$ci['id'] ?>" data-country="<?= (int)$ci['country_id'] ?>" data-department="<?= (int)($ci['department_id'] ?? 0) ?>" <?= (int)($c['city_id'] ?? 0) === (int)$ci['id'] ? 'selected' : '' ?>><?= e($ci['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div>
        <label class="block text-sm font-semibold mb-1">Año fundación</label>
        <input name="founded_year" value="<?= e($c['founded_year'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Sitio web</label>
        <input name="website" value="<?= e($c['website'] ?? '') ?>" placeholder="https://…" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Teléfono / WhatsApp</label>
        <input name="phone" value="<?= e($c['phone'] ?? '') ?>" placeholder="+595 21 XXX XXX" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
    </div>

    <script>
      (function() {
        const c = document.getElementById('me-country');
        const d = document.getElementById('me-dept');
        const ci = document.getElementById('me-city');
        if (!c || !d || !ci) return;
        function applyNoAplica(sel, has) {
          const ph = sel.querySelector('option[value=""]');
          if (!ph) return;
          if (has) ph.textContent = '— Selecciona —';
          else { ph.textContent = 'No aplica para este país'; sel.value = ''; }
        }
        function sync() {
          const cv = c.value, dv = d.value;
          let dMatches = 0;
          Array.from(d.options).forEach(o => { if (!o.value) return; const m = o.dataset.country === cv; o.hidden = !m; if (m) dMatches++; if (o.hidden && o.selected) d.value = ''; });
          applyNoAplica(d, dMatches > 0);
          let cMatches = 0;
          Array.from(ci.options).forEach(o => { if (!o.value) return; const m = (o.dataset.country === cv) && (!dv || o.dataset.department === dv); o.hidden = !m; if (m) cMatches++; if (o.hidden && o.selected) ci.value = ''; });
          applyNoAplica(ci, cMatches > 0);
        }
        c.addEventListener('change', sync); d.addEventListener('change', sync); sync();
      })();
    </script>

    <div>
      <label class="block text-sm font-semibold mb-1">Logo</label>
      <?php if (!empty($c['logo_image'])): ?><div class="mb-2"><img src="<?= e(img_url($c['logo_image'])) ?>" class="h-16" /></div><?php endif; ?>
      <input type="file" name="logo" accept="image/*" />
    </div>

    <div class="border border-gray-200 rounded p-4 space-y-2">
      <p class="font-semibold text-sm">Visibilidad y notificaciones</p>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= !empty($c['visibility_email']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar email de contacto</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= !empty($c['visibility_website']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar sitio web</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_phone" value="1" <?= !empty($c['visibility_phone']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar teléfono / WhatsApp</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="notifications_opt_in" value="1" <?= !empty($c['notifications_opt_in']) ? 'checked' : '' ?> class="accent-naranja" /> Recibir notificaciones por email</label>
    </div>

    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar cambios</button>
  </form>

<?php elseif ($tab === 'ofertas'): ?>
  <?php if ($c['status'] !== 'active'): ?>
    <div class="bg-orange-50 border border-naranja rounded p-4 mb-6 text-sm">
      Tu empresa está pendiente de aprobación. Podrás publicar ofertas en cuanto sea aprobada.
    </div>
  <?php endif; ?>

  <details class="bg-white border border-gray-200 rounded-lg p-6 mb-6" <?= $c['status']==='active' ? 'open' : '' ?>>
    <summary class="font-bold cursor-pointer">+ Publicar nueva oferta</summary>
    <?php if ($c['status'] === 'active'): ?>
    <form method="post" enctype="multipart/form-data" class="mt-4 space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <input type="hidden" name="action" value="publish_offer" />
      <div><label class="block text-sm font-semibold mb-1">Título *</label><input name="title" required class="w-full border border-gray-300 rounded px-3 py-2" /></div>
      <div><label class="block text-sm font-semibold mb-1">Descripción *</label><textarea name="description" required rows="5" class="w-full border border-gray-300 rounded px-3 py-2"></textarea></div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><label class="block text-sm font-semibold mb-1">Categoría</label><input name="category" placeholder="Seguridad, Calidad…" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
        <div><label class="block text-sm font-semibold mb-1">Modalidad *</label>
          <select name="modality" required class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">—</option>
            <?php foreach (['presencial','remoto','hibrido'] as $m): ?><option value="<?= $m ?>"><?= ucfirst($m) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div><label class="block text-sm font-semibold mb-1">País</label>
          <select name="country_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <?php foreach ($countries as $cc): ?><option value="<?= (int)$cc['id'] ?>" <?= (int)($c['country_id'] ?? 0) === (int)$cc['id'] ? 'selected' : '' ?>><?= e($cc['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="block text-sm font-semibold mb-1">Salario mínimo (Gs.)</label><input name="salary_min" type="number" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
        <div><label class="block text-sm font-semibold mb-1">Salario máximo (Gs.)</label><input name="salary_max" type="number" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Flyer / imagen (opcional)</label>
        <input name="flyer" type="file" accept="image/*" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
        <p class="text-xs text-gris-oscuro mt-1">Se mostrará en la Bolsa y en tu perfil de empresa.</p>
      </div>
      <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Publicar oferta</button>
    </form>
    <?php endif; ?>
  </details>

  <?php if (!$my_offers): ?>
    <p class="text-gris-oscuro">Aún no has publicado ofertas.</p>
  <?php else: ?>
    <ul class="space-y-3">
      <?php foreach ($my_offers as $o): $interest_count = DB::one('SELECT COUNT(*) n FROM job_interests WHERE offer_id = ?', [(int)$o['id']])['n'] ?? 0; ?>
        <li class="bg-white border border-gray-200 rounded-lg p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
              <?php if (!empty($o['flyer_image'])): ?>
                <img src="<?= e(img_url($o['flyer_image'])) ?>" alt="" class="w-16 h-16 object-cover rounded border border-gray-200 shrink-0" />
              <?php endif; ?>
              <div class="min-w-0">
                <h3 class="font-bold text-lg"><?= e($o['title']) ?></h3>
                <p class="text-xs text-gris-oscuro mt-1">
                  <span class="font-semibold uppercase"><?= e($o['status']) ?></span> ·
                  <?= e($o['modality']) ?> · <?= e($o['category'] ?? '—') ?> · publicada <?= e(format_date($o['created_at'])) ?>
                </p>
              </div>
            </div>
            <div class="text-right">
              <a href="<?= e(u('/mi-empresa?tab=interesados&offer=' . (int)$o['id'])) ?>" class="text-sm text-azul font-semibold hover:underline"><?= (int)$interest_count ?> interesado<?= (int)$interest_count === 1 ? '' : 's' ?> →</a>
              <div class="mt-2 flex gap-2">
                <?php if ($o['status'] === 'published'): ?>
                  <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" /><input type="hidden" name="action" value="close_offer" /><input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>" /><button class="text-xs border border-coral text-coral px-3 py-1 rounded hover:bg-red-50">Cerrar</button></form>
                <?php elseif ($o['status'] === 'closed'): ?>
                  <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" /><input type="hidden" name="action" value="reopen_offer" /><input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>" /><button class="text-xs border border-azul text-azul px-3 py-1 rounded hover:bg-blue-50">Reabrir</button></form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php if ($o['description']): ?><p class="text-sm text-gris-oscuro mt-3"><?= e(mb_strimwidth($o['description'], 0, 180, '…')) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php elseif ($tab === 'interesados'): ?>
  <?php $filter_offer = isset($_GET['offer']) ? (int)$_GET['offer'] : 0;
        $filtered = $filter_offer ? array_filter($my_interests, fn($i) => (int)$i['offer_id'] === $filter_offer) : $my_interests; ?>
  <?php if ($filter_offer): ?>
    <p class="mb-4 text-sm">Filtrando interesados en oferta #<?= $filter_offer ?>. <a href="<?= e(u('/mi-empresa?tab=interesados')) ?>" class="text-azul hover:underline">Ver todos</a></p>
  <?php endif; ?>
  <?php if (!$filtered): ?>
    <p class="text-gris-oscuro">No tienes interesados todavía.</p>
  <?php else: ?>
    <ul class="space-y-3">
      <?php foreach ($filtered as $i): ?>
        <li class="bg-white border border-gray-200 rounded-lg p-5 flex flex-wrap gap-3 items-start">
          <div class="flex-1 min-w-[200px]">
            <p class="text-xs text-gris-oscuro uppercase font-bold">Oferta: <?= e($i['offer_title']) ?></p>
            <p class="font-bold mt-1"><?= e($i['guest_name']) ?></p>
            <p class="text-sm text-azul"><a href="mailto:<?= e($i['guest_email']) ?>"><?= e($i['guest_email']) ?></a></p>
            <?php if ($i['professional_id']): $pr = ProfessionalRepo::find((int)$i['professional_id']); ?>
              <?php if ($pr): ?><p class="text-xs text-gris-oscuro mt-1">Perfil en la red: <a href="<?= e(profile_url($pr)) ?>" class="text-azul hover:underline" target="_blank"><?= e($pr['name']) ?> →</a></p><?php endif; ?>
            <?php endif; ?>
            <?php if ($i['message']): ?><p class="text-sm text-gris-oscuro mt-2 italic">"<?= e($i['message']) ?>"</p><?php endif; ?>
          </div>
          <p class="text-xs text-gris-oscuro opacity-70 shrink-0"><?= e(format_date($i['created_at'])) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

<?php elseif ($tab === 'password'): ?>
  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-4 max-w-lg">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_password" />
    <div>
      <label class="block text-sm font-semibold mb-1">Contraseña actual</label>
      <input name="current_password" type="password" required class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Nueva contraseña</label>
      <input name="new_password" type="password" required minlength="8" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Confirmar nueva contraseña</label>
      <input name="new_password_confirm" type="password" required minlength="8" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>
    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Actualizar contraseña</button>
  </form>
<?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
