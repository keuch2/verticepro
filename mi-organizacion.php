<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
require_once __DIR__ . '/includes/image.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$u = require_company();
$c = current_company();

if (!$c) {
    $page_title = 'Mi organización — Vértice Pro';
    include __DIR__ . '/includes/header.php';
    echo '<section class="max-w-3xl mx-auto px-6 py-16"><p>No encontramos el perfil de organización vinculado a tu cuenta. Contacta a soporte.</p></section>';
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
                // Servicios ofrecidos (M:N)
                if (isset($_POST['services'])) {
                    CompanyRepo::setServices((int)$c['id'], (array)$_POST['services']);
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

            case 'update_application_status':
                $iid    = (int)($_POST['interest_id'] ?? 0);
                $status = $_POST['new_status'] ?? '';
                if (!in_array($status, ['received','reviewed','shortlisted','rejected'], true)) {
                    throw new RuntimeException('Estado inválido.');
                }
                // Validar que la postulación pertenece a una oferta de la empresa
                $row = DB::one(
                    'SELECT i.*, j.title AS offer_title
                     FROM job_interests i JOIN job_offers j ON j.id = i.offer_id
                     WHERE i.id = ? AND j.company_id = ? LIMIT 1',
                    [$iid, (int)$c['id']]
                );
                if (!$row) throw new RuntimeException('Postulación no encontrada.');
                DB::update('job_interests', [
                    'status' => $status,
                    'status_updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $iid]);

                // Notificar al postulante
                $status_label = [
                    'received'    => 'Recibida',
                    'reviewed'    => 'Revisada',
                    'shortlisted' => 'Preseleccionada',
                    'rejected'    => 'No avanza',
                ][$status];
                $title = 'Cambio de estado en tu postulación';
                $body  = 'La organización ' . $c['name'] . ' actualizó el estado de tu postulación a "' . $row['offer_title'] . '": ahora figura como "' . $status_label . '".';
                $link  = u('/mi-perfil?tab=intereses');
                if (!empty($row['user_id'])) {
                    Notify::create((int)$row['user_id'], 'application_status_changed', $title, $body, $link, $row['guest_email']);
                } elseif (!empty($row['guest_email'])) {
                    Notify::emailOnly($row['guest_email'], (string)$row['guest_name'], 1, $title, $body, $link);
                }
                $ok = 'Estado actualizado.';
                $tab = 'interesados';
                break;

            case 'send_application_message':
                $iid  = (int)($_POST['interest_id'] ?? 0);
                $body = trim($_POST['message'] ?? '');
                if ($body === '') throw new RuntimeException('Escribí un mensaje.');
                $row = DB::one(
                    'SELECT i.*, j.title AS offer_title
                     FROM job_interests i JOIN job_offers j ON j.id = i.offer_id
                     WHERE i.id = ? AND j.company_id = ? LIMIT 1',
                    [$iid, (int)$c['id']]
                );
                if (!$row) throw new RuntimeException('Postulación no encontrada.');
                DB::insert('application_messages', [
                    'interest_id'    => $iid,
                    'sender_role'    => 'company',
                    'sender_user_id' => (int)$u['id'],
                    'body'           => $body,
                ]);
                $title = 'Nuevo mensaje sobre tu postulación';
                $msg   = 'La organización ' . $c['name'] . ' te envió un mensaje sobre tu postulación a "' . $row['offer_title'] . '":' . "\n\n" . $body;
                $link  = u('/mi-perfil?tab=intereses&open=' . $iid);
                if (!empty($row['user_id'])) {
                    Notify::create((int)$row['user_id'], 'application_message', $title, $msg, $link, $row['guest_email']);
                } elseif (!empty($row['guest_email'])) {
                    Notify::emailOnly($row['guest_email'], (string)$row['guest_name'], 1, $title, $msg, $link);
                }
                $ok = 'Mensaje enviado.';
                $tab = 'interesados';
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
$companyServices = SectionRepo::companyServices();
$countries = SectionRepo::countries();
$cities    = SectionRepo::cities();
$my_offers = DB::all('SELECT * FROM job_offers WHERE company_id = ? ORDER BY created_at DESC', [(int)$c['id']]);
$my_interests = DB::all(
    'SELECT i.*, o.title AS offer_title FROM job_interests i JOIN job_offers o ON o.id = i.offer_id WHERE o.company_id = ? ORDER BY i.created_at DESC',
    [(int)$c['id']]
);

$page_title = 'Mi organización — Vértice Pro';
$page_active = 'mi-organizacion.php';
include __DIR__ . '/includes/header.php';

function tab_link_emp(string $t, string $current, string $label, ?int $count = null): string {
    $cls = $t === $current ? 'border-naranja text-naranja' : 'border-transparent text-gris-oscuro hover:text-naranja';
    $url = e(u('/mi-organizacion?tab=' . $t));
    $badge = $count !== null ? '<span class="ml-1.5 text-xs bg-gray-200 text-gris-oscuro rounded-full px-1.5">' . $count . '</span>' : '';
    return '<a href="' . $url . '" class="px-1 py-3 border-b-2 ' . $cls . ' font-semibold text-sm transition">' . e($label) . $badge . '</a>';
}
?>
<?php $__has_prof = DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$u['id']]); ?>
<?php if (!$__has_prof): ?>
  <div class="max-w-5xl mx-auto px-6 mt-4">
    <div class="bg-azul/10 border border-azul rounded-lg px-4 py-3 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-texto">¿Querés sumar tu perfil profesional? Podés crearlo vinculado a esta misma cuenta y aparecer en la Red Vértice Pro.</p>
      <a href="<?= e(u('/crear-perfil')) ?>" class="bg-azul text-white text-sm font-semibold px-4 py-2 rounded hover:bg-blue-700 transition">+ Crear perfil profesional</a>
    </div>
  </div>
<?php endif; ?>

<section class="bg-gris-claro py-8 px-6">
  <div class="max-w-5xl mx-auto flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-2xl font-extrabold">Mi organización</h1>
      <p class="text-gris-oscuro text-sm mt-1"><?= e($c['name']) ?> · <a href="<?= e(u('/organizacion/' . $c['slug'])) ?>" target="_blank" class="text-azul hover:underline">Ver perfil público →</a></p>
    </div>
    <div class="text-sm">
      <?php if ($c['status'] === 'pending'): ?>
        <span class="bg-orange-100 text-naranja px-3 py-1 rounded-full font-semibold">Pendiente de aprobación</span>
      <?php elseif ($c['status'] === 'active'): ?>
        <span class="bg-verde/10 text-verde px-3 py-1 rounded-full font-semibold">Organización activa</span>
      <?php else: ?>
        <span class="bg-red-50 text-coral px-3 py-1 rounded-full font-semibold"><?= e(ucfirst($c['status'])) ?></span>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-6">
  <nav class="flex flex-wrap gap-5 border-b border-gray-200">
    <?= tab_link_emp('datos',       $tab, 'Datos de la organización') ?>
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
      <!-- Centinela: garantiza que 'sectors' llegue en el POST aunque se desmarquen todos (permite limpiar). -->
      <input type="hidden" name="sectors[]" value="" />
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($sectors as $s): $checked = in_array((int)$s['id'], $current_sectors, true); ?>
          <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
            <input type="checkbox" name="sectors[]" value="<?= (int)$s['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
            <span><?= e($s['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <?php $current_services = CompanyRepo::serviceIds((int)$c['id']); ?>
      <label class="block text-sm font-semibold mb-2">Servicios que ofrecemos</label>
      <!-- Centinela: garantiza que 'services' llegue en el POST aunque se desmarquen todos. -->
      <input type="hidden" name="services[]" value="" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <?php foreach ($companyServices as $sv): $checked = in_array((int)$sv['id'], $current_services, true); ?>
          <label class="flex items-start gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
            <input type="checkbox" name="services[]" value="<?= (int)$sv['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja mt-0.5" />
            <span><?= e($sv['name']) ?></span>
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
      Tu organización está pendiente de aprobación. Podrás publicar ofertas en cuanto sea aprobada.
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
        <p class="text-xs text-gris-oscuro mt-1">Se mostrará en la Bolsa y en tu perfil de organización.</p>
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
              <a href="<?= e(u('/mi-organizacion?tab=interesados&offer=' . (int)$o['id'])) ?>" class="text-sm text-azul font-semibold hover:underline"><?= (int)$interest_count ?> interesado<?= (int)$interest_count === 1 ? '' : 's' ?> →</a>
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
  <?php
    $filter_offer = isset($_GET['offer']) ? (int)$_GET['offer'] : 0;
    $filtered = $filter_offer ? array_filter($my_interests, fn($i) => (int)$i['offer_id'] === $filter_offer) : $my_interests;
    $open_interest = isset($_GET['open']) ? (int)$_GET['open'] : 0;
    $status_styles = [
      'received'    => 'bg-gray-100 text-gris-oscuro',
      'reviewed'    => 'bg-azul/10 text-azul',
      'shortlisted' => 'bg-verde/10 text-verde',
      'rejected'    => 'bg-red-50 text-coral',
    ];
    $status_labels = [
      'received'    => 'Recibida',
      'reviewed'    => 'Revisada',
      'shortlisted' => 'Preseleccionada',
      'rejected'    => 'No avanza',
    ];
  ?>
  <?php if ($filter_offer): ?>
    <p class="mb-4 text-sm">Filtrando interesados en oferta #<?= $filter_offer ?>. <a href="<?= e(u('/mi-organizacion?tab=interesados')) ?>" class="text-azul hover:underline">Ver todos</a></p>
  <?php endif; ?>
  <?php if (!$filtered): ?>
    <p class="text-gris-oscuro">No tienes interesados todavía.</p>
  <?php else: ?>
    <ul class="space-y-3">
      <?php foreach ($filtered as $i):
        $pr = $i['professional_id'] ? ProfessionalRepo::find((int)$i['professional_id']) : null;
        $ptypes = $pr ? ProfessionalRepo::types((int)$pr['id']) : [];
        $st = $i['status'] ?? 'received';
        $is_open = $open_interest === (int)$i['id'];
        $messages = DB::all('SELECT * FROM application_messages WHERE interest_id = ? ORDER BY created_at ASC', [(int)$i['id']]);
      ?>
        <li class="bg-white border border-gray-200 rounded-lg p-5">
          <div class="flex flex-wrap gap-4 items-start">
            <?php if ($pr && !empty($pr['avatar_image'])): ?>
              <img src="<?= e(img_url($pr['avatar_image'])) ?>" alt="" class="w-16 h-16 rounded-full object-cover shrink-0" />
            <?php else: ?>
              <div class="w-16 h-16 rounded-full bg-naranja flex items-center justify-center text-white font-bold text-lg shrink-0">
                <?= e(mb_substr($i['guest_name'] ?? '?', 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div class="flex-1 min-w-[200px]">
              <p class="text-xs text-gris-oscuro uppercase font-bold">Oferta: <?= e($i['offer_title']) ?></p>
              <p class="font-bold mt-1"><?= e($i['guest_name']) ?></p>
              <p class="text-sm text-azul mt-0.5"><a href="mailto:<?= e($i['guest_email']) ?>"><?= e($i['guest_email']) ?></a></p>
              <?php if ($pr): ?>
                <p class="text-xs text-gris-oscuro mt-1"><?= e($pr['title'] ?? '') ?></p>
                <?php if ($ptypes): ?>
                  <p class="text-xs text-gris-oscuro mt-0.5"><?= e(implode(' · ', array_column($ptypes, 'name'))) ?></p>
                <?php endif; ?>
                <?php if (!empty($pr['bio'])): ?>
                  <p class="text-sm text-gris-oscuro mt-2"><?= e(mb_strimwidth($pr['bio'], 0, 160, '…')) ?></p>
                <?php endif; ?>
                <a href="<?= e(profile_url($pr)) ?>" target="_blank" class="text-xs text-azul hover:underline mt-1 inline-block">Ver perfil completo →</a>
              <?php endif; ?>
              <?php if ($i['message']): ?>
                <p class="text-sm text-gris-oscuro mt-2 italic">"<?= e($i['message']) ?>"</p>
              <?php endif; ?>
            </div>
            <div class="text-right shrink-0 space-y-2">
              <span class="inline-block text-xs px-2 py-1 rounded-full font-semibold <?= e($status_styles[$st]) ?>"><?= e($status_labels[$st]) ?></span>
              <p class="text-xs text-gris-oscuro opacity-70"><?= e(format_date($i['created_at'])) ?></p>
              <form method="post" class="flex items-center gap-2">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="update_application_status" />
                <input type="hidden" name="interest_id" value="<?= (int)$i['id'] ?>" />
                <select name="new_status" class="text-xs border border-gray-300 rounded px-2 py-1">
                  <?php foreach ($status_labels as $k => $lbl): ?>
                    <option value="<?= e($k) ?>" <?= $st===$k?'selected':'' ?>><?= e($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="text-xs bg-azul text-white px-2 py-1 rounded hover:bg-blue-700" type="submit">Aplicar</button>
              </form>
              <a href="<?= e(u('/mi-organizacion?tab=interesados' . ($filter_offer ? '&offer=' . $filter_offer : '') . ($is_open ? '' : '&open=' . (int)$i['id']))) ?>#int-<?= (int)$i['id'] ?>" id="int-<?= (int)$i['id'] ?>" class="text-xs text-azul hover:underline">
                💬 <?= $is_open ? 'Ocultar conversación' : 'Ver conversación' ?> <?= count($messages) ? '(' . count($messages) . ')' : '' ?>
              </a>
            </div>
          </div>

          <?php if ($is_open): ?>
            <div class="mt-5 pt-5 border-t border-gray-200">
              <?php if ($messages): ?>
                <ul class="space-y-3 mb-4">
                  <?php foreach ($messages as $m): $is_company = $m['sender_role'] === 'company'; ?>
                    <li class="flex <?= $is_company ? 'justify-end' : 'justify-start' ?>">
                      <div class="max-w-[80%] <?= $is_company ? 'bg-azul/10 text-texto' : 'bg-gray-100 text-texto' ?> rounded-lg px-3 py-2">
                        <p class="text-xs font-semibold mb-1 <?= $is_company ? 'text-azul' : 'text-gris-oscuro' ?>"><?= $is_company ? 'Tú (organización)' : e($i['guest_name']) ?></p>
                        <p class="text-sm whitespace-pre-line"><?= e($m['body']) ?></p>
                        <p class="text-[10px] opacity-60 mt-1"><?= e(format_date($m['created_at'])) ?></p>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-sm text-gris-oscuro mb-3">No hay mensajes todavía. Inicia la conversación.</p>
              <?php endif; ?>
              <form method="post" class="flex flex-col gap-2">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
                <input type="hidden" name="action" value="send_application_message" />
                <input type="hidden" name="interest_id" value="<?= (int)$i['id'] ?>" />
                <textarea name="message" rows="2" required placeholder="Escribí un mensaje al postulante…" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>
                <div class="text-right">
                  <button class="bg-azul text-white text-sm font-semibold px-4 py-2 rounded hover:bg-blue-700" type="submit">Enviar mensaje</button>
                </div>
              </form>
            </div>
          <?php endif; ?>
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
