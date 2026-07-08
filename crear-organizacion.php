<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$u = require_public_user();

// Si ya tiene empresa vinculada, redirigir al dashboard.
$already = DB::one('SELECT id FROM companies WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
if ($already) { redirect('/mi-organizacion'); }

$errors = [];
$old = [
    'name' => '', 'description' => '',
    'sectors' => [], 'services' => [], 'country_id' => '', 'department_id' => '', 'city_id' => '',
    'founded_year' => '', 'website' => '', 'phone' => '',
    'visibility_email' => 1, 'visibility_website' => 1, 'visibility_phone' => 0,
    'notifications_opt_in' => 1,
];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['name']         = trim($_POST['name'] ?? '');
    $old['description']  = trim($_POST['description'] ?? '');
    $old['sectors']      = array_map('intval', (array)($_POST['sectors'] ?? []));
    $old['services']     = array_map('intval', (array)($_POST['services'] ?? []));
    $old['country_id']   = $_POST['country_id'] ?? '';
    $old['department_id']= $_POST['department_id'] ?? '';
    $old['city_id']      = $_POST['city_id'] ?? '';
    $old['founded_year'] = trim($_POST['founded_year'] ?? '');
    $old['website']      = trim($_POST['website'] ?? '');
    $old['phone']        = trim($_POST['phone'] ?? '');
    $old['visibility_email']    = !empty($_POST['visibility_email']) ? 1 : 0;
    $old['visibility_website']  = !empty($_POST['visibility_website']) ? 1 : 0;
    $old['visibility_phone']    = !empty($_POST['visibility_phone']) ? 1 : 0;
    $old['notifications_opt_in']= !empty($_POST['notifications_opt_in']) ? 1 : 0;

    if ($old['name'] === '')         $errors['name'] = 'Indica el nombre de la organización.';
    if ($old['country_id'] === '')   $errors['country_id'] = 'Selecciona un país.';
    if (empty($old['sectors']))      $errors['sectors'] = 'Selecciona al menos un sector.';

    if ($old['website'] !== '' && !preg_match('#^https?://#i', $old['website'])) $errors['website'] = 'Debe empezar con http(s)://';

    if (!$errors) {
        $base = slugify($old['name']) ?: 'empresa';
        $slug = $base; $i = 2;
        while (DB::one('SELECT id FROM companies WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }

        $primary_sector = !empty($old['sectors']) ? (int)$old['sectors'][0] : null;
        // Email: reutilizamos el del user.
        $cid = DB::insert('companies', [
            'user_id'      => (int)$u['id'],
            'slug'         => $slug,
            'name'         => $old['name'],
            'description'  => $old['description'] ?: null,
            'sector_id'    => $primary_sector,
            'country_id'   => (int)$old['country_id'],
            'city_id'      => $old['city_id'] !== '' ? (int)$old['city_id'] : null,
            'founded_year' => $old['founded_year'] !== '' ? (int)$old['founded_year'] : null,
            'website'      => $old['website'] ?: null,
            'phone'        => $old['phone'] ?: null,
            'email'        => $u['email'],
            'verified'     => 0,
            'visibility_email'    => $old['visibility_email'],
            'visibility_website'  => $old['visibility_website'],
            'visibility_phone'    => $old['visibility_phone'],
            'notifications_opt_in'=> $old['notifications_opt_in'],
            'status'       => 'pending',
        ]);

        if (!empty($old['sectors']))  CompanyRepo::setSectors($cid, $old['sectors']);
        if (!empty($old['services'])) CompanyRepo::setServices($cid, $old['services']);

        $submitted_ok = true;
    }
}

$sectors         = SectionRepo::sectors();
$companyServices = SectionRepo::companyServices();
$countries       = SectionRepo::countries();
$departments     = SectionRepo::departments();
$cities          = SectionRepo::cities();
$default_country_id = (int)(DB::one("SELECT id FROM countries WHERE slug = 'paraguay'")['id'] ?? 0);
$selected_country = $old['country_id'] !== '' ? (int)$old['country_id'] : $default_country_id;

$page_title = 'Crear mi perfil de organización — Vértice Pro';
$page_active = 'crear-organizacion.php';
include __DIR__ . '/includes/header.php';
?>
<section class="bg-gris-claro py-8 px-6">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-extrabold">Crear mi perfil de organización</h1>
    <p class="text-gris-oscuro text-sm mt-1">Como <?= e($u['name']) ?>, además de tu perfil profesional vas a tener un perfil de organización vinculado a esta misma cuenta.</p>
  </div>
</section>

<section class="max-w-4xl mx-auto px-6 py-10">
  <?php if ($submitted_ok): ?>
    <div class="bg-verde/10 border border-verde rounded-lg p-6">
      <h2 class="text-xl font-extrabold text-verde">¡Solicitud recibida!</h2>
      <p class="mt-2 text-gris-oscuro">Tu perfil de organización quedó <strong>pendiente de aprobación</strong>. Te avisaremos por email cuando esté publicado y podrás administrarlo desde <a href="<?= e(u('/mi-organizacion')) ?>" class="text-azul hover:underline">Mi organización</a>.</p>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mb-6 text-sm">
        <p class="font-semibold mb-1">Revisa los campos marcados:</p>
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div>
        <label class="block text-sm font-semibold mb-1">Nombre de la organización *</label>
        <input name="name" required value="<?= e($old['name']) ?>" class="w-full border <?= isset($errors['name'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Descripción</label>
        <textarea name="description" rows="4" maxlength="2000" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($old['description']) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Sectores *</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <?php foreach ($sectors as $s): $checked = in_array((int)$s['id'], $old['sectors'], true); ?>
            <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
              <input type="checkbox" name="sectors[]" value="<?= (int)$s['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
              <span><?= e($s['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Servicios que ofrecemos</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <?php foreach ($companyServices as $sv): $checked = in_array((int)$sv['id'], $old['services'], true); ?>
            <label class="flex items-start gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
              <input type="checkbox" name="services[]" value="<?= (int)$sv['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja mt-0.5" />
              <span><?= e($sv['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">País *</label>
          <select id="ce-country" name="country_id" required class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <?php foreach ($countries as $cc): ?><option value="<?= (int)$cc['id'] ?>" <?= (int)$selected_country === (int)$cc['id'] ? 'selected':'' ?>><?= e($cc['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Departamento</label>
          <select id="ce-dept" name="department_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">— Selecciona —</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int)$d['id'] ?>" data-country="<?= (int)$d['country_id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Ciudad</label>
          <select id="ce-city" name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">— Selecciona —</option>
            <?php foreach ($cities as $ci): ?>
              <option value="<?= (int)$ci['id'] ?>" data-country="<?= (int)$ci['country_id'] ?>" data-department="<?= (int)($ci['department_id'] ?? 0) ?>"><?= e($ci['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">Año de fundación</label>
          <input name="founded_year" value="<?= e($old['founded_year']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Sitio web</label>
          <input name="website" value="<?= e($old['website']) ?>" placeholder="https://..." class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Teléfono / WhatsApp</label>
          <input name="phone" value="<?= e($old['phone']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
      </div>

      <div class="border border-gray-200 rounded p-4 space-y-2">
        <p class="font-semibold text-sm">Visibilidad y notificaciones</p>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= $old['visibility_email'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar email</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= $old['visibility_website'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar sitio web</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_phone" value="1" <?= $old['visibility_phone'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar teléfono</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="notifications_opt_in" value="1" <?= $old['notifications_opt_in'] ? 'checked' : '' ?> class="accent-naranja" /> Recibir notificaciones por email</label>
      </div>

      <div class="flex gap-3">
        <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Enviar para revisión</button>
        <a href="<?= e(u('/mi-perfil')) ?>" class="text-gris-oscuro hover:text-naranja text-sm py-2.5">Cancelar</a>
      </div>
    </form>

    <script>
      (function() {
        const c = document.getElementById('ce-country');
        const d = document.getElementById('ce-dept');
        const ci = document.getElementById('ce-city');
        if (!c || !d || !ci) return;
        function applyNoAplica(sel, has) {
          const ph = sel.querySelector('option[value=""]');
          if (!ph) return;
          if (has) ph.textContent = '— Selecciona —';
          else { ph.textContent = 'No aplica para este país'; sel.value = ''; }
        }
        function sync() {
          const cv = c.value, dv = d.value;
          let dM = 0;
          Array.from(d.options).forEach(o => { if (!o.value) return; const m = o.dataset.country === cv; o.hidden = !m; if (m) dM++; if (o.hidden && o.selected) d.value=''; });
          applyNoAplica(d, dM>0);
          let cM = 0;
          Array.from(ci.options).forEach(o => { if (!o.value) return; const m = (o.dataset.country === cv) && (!dv || o.dataset.department === dv); o.hidden = !m; if (m) cM++; if (o.hidden && o.selected) ci.value=''; });
          applyNoAplica(ci, cM>0);
        }
        c.addEventListener('change', sync); d.addEventListener('change', sync); sync();
      })();
    </script>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
