<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
require_once __DIR__ . '/includes/image.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$u = require_professional();
$p = current_professional();

// Si el usuario logueado es admin (también pasa el guard), permitir editar via ?id=
if (!$p && in_array($u['role'], ['admin', 'author'], true)) {
    redirect('/admin/profesionales/');
}

if (!$p) {
    // El user es professional pero no hay fila de profesional vinculada — caso raro
    $page_title = 'Mi perfil — Vértice Pro';
    include __DIR__ . '/includes/header.php';
    echo '<section class="max-w-3xl mx-auto px-6 py-16"><p>No encontramos tu perfil profesional vinculado. Contacta a soporte.</p></section>';
    include __DIR__ . '/includes/footer.php';
    return;
}

$tab = $_GET['tab'] ?? 'datos';
$valid_tabs = ['datos', 'disciplinas', 'formacion', 'experiencia', 'servicios', 'password'];
if (!in_array($tab, $valid_tabs, true)) $tab = 'datos';

$ok = null;
$err = null;

// ============================
// Handlers POST por tab
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'save_datos':
                $data = [
                    'name'     => trim($_POST['name'] ?? ''),
                    'title'    => trim($_POST['title'] ?? ''),
                    'bio'      => trim($_POST['bio'] ?? '') ?: null,
                    'city_id'  => !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null,
                    'type_id'  => !empty($_POST['type_id']) ? (int)$_POST['type_id'] : null,
                    'linkedin' => trim($_POST['linkedin'] ?? '') ?: null,
                    'website'  => trim($_POST['website'] ?? '') ?: null,
                    'phone'    => trim($_POST['phone'] ?? '') ?: null,
                    'company_id' => !empty($_POST['company_id']) ? (int)$_POST['company_id'] : null,
                    'available'           => !empty($_POST['available']) ? 1 : 0,
                    'visibility_email'    => !empty($_POST['visibility_email'])    ? 1 : 0,
                    'visibility_linkedin' => !empty($_POST['visibility_linkedin']) ? 1 : 0,
                    'visibility_website'  => !empty($_POST['visibility_website'])  ? 1 : 0,
                    'visibility_phone'    => !empty($_POST['visibility_phone'])    ? 1 : 0,
                    'notifications_opt_in'=> !empty($_POST['notifications_opt_in']) ? 1 : 0,
                ];
                if ($data['name'] === '')  throw new RuntimeException('El nombre es obligatorio.');
                if ($data['title'] === '') throw new RuntimeException('El título profesional es obligatorio.');
                if ($data['linkedin'] && !preg_match('#^https?://#i', $data['linkedin'])) throw new RuntimeException('LinkedIn debe empezar con http(s)://');
                if ($data['website']  && !preg_match('#^https?://#i', $data['website']))  throw new RuntimeException('Website debe empezar con http(s)://');

                if (!empty($_FILES['avatar']['name'])) {
                    $rel = upload_image($_FILES['avatar'], 'profiles', $p['slug']);
                    if ($rel) $data['avatar_image'] = $rel;
                }
                DB::update('professionals', $data, ['id' => (int)$p['id']]);
                // Sincronizar nombre con users
                if (!empty($p['user_id'])) {
                    DB::update('users', ['name' => $data['name']], ['id' => (int)$p['user_id']]);
                }
                $ok = 'Datos actualizados.';
                $tab = 'datos';
                break;

            case 'save_disciplinas':
                $ids = array_map('intval', (array)($_POST['disciplines'] ?? []));
                $specs = array_filter(array_map('trim', explode(',', $_POST['specialties'] ?? '')));
                DB::run('DELETE FROM professional_disciplines WHERE professional_id = ?', [(int)$p['id']]);
                foreach ($ids as $did) {
                    if ($did > 0) {
                        try { DB::insert('professional_disciplines', ['professional_id' => (int)$p['id'], 'discipline_id' => $did]); } catch (\Throwable $e) {}
                    }
                }
                DB::run('DELETE FROM professional_specialties WHERE professional_id = ?', [(int)$p['id']]);
                foreach ($specs as $s) {
                    try { DB::insert('professional_specialties', ['professional_id' => (int)$p['id'], 'specialty' => $s]); } catch (\Throwable $e) {}
                }
                $ok = 'Disciplinas y especialidades actualizadas.';
                $tab = 'disciplinas';
                break;

            case 'save_formacion':
                $rows = (array)($_POST['formacion'] ?? []);
                DB::run('DELETE FROM professional_formation WHERE professional_id = ?', [(int)$p['id']]);
                $i = 0;
                foreach ($rows as $r) {
                    $degree = trim($r['degree'] ?? '');
                    if ($degree === '') continue;
                    DB::insert('professional_formation', [
                        'professional_id' => (int)$p['id'],
                        'degree'          => $degree,
                        'institution'     => trim($r['institution'] ?? ''),
                        'date_from'       => trim($r['date_from'] ?? '') ?: null,
                        'date_to'         => trim($r['date_to'] ?? '') ?: null,
                        'details'         => trim($r['details'] ?? '') ?: null,
                        'sort_order'      => $i++,
                    ]);
                }
                $ok = 'Formación actualizada.';
                $tab = 'formacion';
                break;

            case 'save_experiencia':
                $rows = (array)($_POST['experiencia'] ?? []);
                DB::run('DELETE FROM professional_experience WHERE professional_id = ?', [(int)$p['id']]);
                $i = 0;
                foreach ($rows as $r) {
                    $title = trim($r['job_title'] ?? '');
                    if ($title === '') continue;
                    DB::insert('professional_experience', [
                        'professional_id' => (int)$p['id'],
                        'job_title'       => $title,
                        'company'         => trim($r['company'] ?? ''),
                        'date_from'       => trim($r['date_from'] ?? '') ?: null,
                        'date_to'         => trim($r['date_to'] ?? '') ?: null,
                        'description'     => trim($r['description'] ?? '') ?: null,
                        'sort_order'      => $i++,
                    ]);
                }
                $ok = 'Experiencia actualizada.';
                $tab = 'experiencia';
                break;

            case 'save_servicios':
                $rows = (array)($_POST['servicios'] ?? []);
                DB::run('DELETE FROM professional_services WHERE professional_id = ?', [(int)$p['id']]);
                $i = 0;
                foreach ($rows as $r) {
                    $title = trim($r['title'] ?? '');
                    if ($title === '') continue;
                    DB::insert('professional_services', [
                        'professional_id' => (int)$p['id'],
                        'title'           => $title,
                        'description'     => trim($r['description'] ?? '') ?: null,
                        'sort_order'      => $i++,
                    ]);
                }
                $ok = 'Servicios actualizados.';
                $tab = 'servicios';
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
        // Refresh $p para que la UI muestre los cambios
        $p = current_professional();
    } catch (\Throwable $e) {
        $err = $e->getMessage();
    }
}

// Datos para la UI
$cities       = SectionRepo::cities();
$types        = SectionRepo::profTypes();
$disciplines  = SectionRepo::disciplines();
$current_disc = array_column(DB::all('SELECT discipline_id FROM professional_disciplines WHERE professional_id = ?', [(int)$p['id']]), 'discipline_id');
$current_spec = ProfessionalRepo::specialties((int)$p['id']);
$formacion    = ProfessionalRepo::formation((int)$p['id']);
$experiencia  = ProfessionalRepo::experience((int)$p['id']);
$servicios    = ProfessionalRepo::services((int)$p['id']);

$page_title = 'Mi perfil — Vértice Pro';
$page_active = 'mi-perfil.php';
include __DIR__ . '/includes/header.php';

function tab_link(string $t, string $current, string $label, ?int $count = null): string {
    $cls = $t === $current ? 'border-naranja text-naranja' : 'border-transparent text-gris-oscuro hover:text-naranja';
    $url = e(u('/mi-perfil?tab=' . $t));
    $badge = $count !== null ? '<span class="ml-1.5 text-xs bg-gray-200 text-gris-oscuro rounded-full px-1.5">' . $count . '</span>' : '';
    return '<a href="' . $url . '" class="px-1 py-3 border-b-2 ' . $cls . ' font-semibold text-sm transition">' . e($label) . $badge . '</a>';
}
?>
<section class="bg-gris-claro py-8 px-6">
  <div class="max-w-5xl mx-auto flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-2xl font-extrabold">Mi perfil</h1>
      <p class="text-gris-oscuro text-sm mt-1"><?= e($p['name']) ?> · <?= e($p['title']) ?> · <a href="<?= e(profile_url($p)) ?>" class="text-azul hover:underline" target="_blank">Ver perfil público →</a></p>
    </div>
    <div class="text-sm text-gris-oscuro">
      <?php if ($p['status'] === 'pending'): ?>
        <span class="bg-orange-100 text-naranja px-3 py-1 rounded-full font-semibold">Pendiente de aprobación</span>
      <?php elseif ($p['status'] === 'active'): ?>
        <span class="bg-verde/10 text-verde px-3 py-1 rounded-full font-semibold">Perfil activo</span>
      <?php else: ?>
        <span class="bg-red-50 text-coral px-3 py-1 rounded-full font-semibold"><?= e(ucfirst($p['status'])) ?></span>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-6">
  <nav class="flex flex-wrap gap-5 border-b border-gray-200">
    <?= tab_link('datos',       $tab, 'Datos básicos') ?>
    <?= tab_link('disciplinas', $tab, 'Disciplinas y especialidades') ?>
    <?= tab_link('formacion',   $tab, 'Formación', count($formacion)) ?>
    <?= tab_link('experiencia', $tab, 'Experiencia', count($experiencia)) ?>
    <?= tab_link('servicios',   $tab, 'Servicios ofrecidos', count($servicios)) ?>
    <?= tab_link('password',    $tab, 'Contraseña') ?>
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
        <label class="block text-sm font-semibold mb-1">Nombre completo *</label>
        <input name="name" required value="<?= e($p['name']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input value="<?= e($p['email']) ?>" disabled class="w-full border border-gray-200 bg-gray-50 rounded px-3 py-2 text-gris-oscuro" />
        <p class="text-xs text-gris-oscuro mt-1">El email no se puede cambiar. Contacta a soporte si lo necesitas.</p>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Título profesional *</label>
      <input name="title" required value="<?= e($p['title']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-semibold mb-1">Ciudad</label>
        <select name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
          <option value="">— Selecciona —</option>
          <?php foreach ($cities as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($p['city_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?><?= !empty($c['country_name']) ? ', ' . e($c['country_name']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Tipo de profesional</label>
        <select name="type_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
          <option value="">— Selecciona —</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (int)($p['type_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Bio profesional</label>
      <textarea name="bio" rows="5" maxlength="2000" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($p['bio'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div>
        <label class="block text-sm font-semibold mb-1">LinkedIn</label>
        <input name="linkedin" value="<?= e($p['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/…" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Sitio web</label>
        <input name="website" value="<?= e($p['website'] ?? '') ?>" placeholder="https://…" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Teléfono / WhatsApp</label>
        <input name="phone" value="<?= e($p['phone'] ?? '') ?>" placeholder="+595 9XX XXX XXX" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Empresa donde trabajo (opcional)</label>
      <?php $allCompanies = DB::all('SELECT id, name FROM companies WHERE status = "active" ORDER BY name'); ?>
      <select name="company_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
        <option value="">— Ninguna —</option>
        <?php foreach ($allCompanies as $ac): ?>
          <option value="<?= (int)$ac['id'] ?>" <?= (int)($p['company_id'] ?? 0) === (int)$ac['id'] ? 'selected' : '' ?>><?= e($ac['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <p class="text-xs text-gris-oscuro mt-1">Si trabajas en una empresa registrada en Vértice Pro, aparecerás en su equipo público.</p>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Foto / Avatar</label>
      <?php if (!empty($p['avatar_image'])): ?><div class="mb-2"><img src="<?= e(img_url($p['avatar_image'])) ?>" class="w-20 h-20 rounded-full object-cover" /></div><?php endif; ?>
      <input type="file" name="avatar" accept="image/*" />
    </div>

    <div class="border border-gray-200 rounded p-4 space-y-2">
      <p class="font-semibold text-sm">Disponibilidad y visibilidad</p>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="available" value="1" <?= !empty($p['available']) ? 'checked' : '' ?> class="accent-naranja" /> Estoy disponible para nuevos proyectos</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= !empty($p['visibility_email']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi email en el perfil público</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_linkedin" value="1" <?= !empty($p['visibility_linkedin']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi LinkedIn</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= !empty($p['visibility_website']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi sitio web</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_phone" value="1" <?= !empty($p['visibility_phone']) ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi teléfono / WhatsApp</label>
      <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="notifications_opt_in" value="1" <?= !empty($p['notifications_opt_in']) ? 'checked' : '' ?> class="accent-naranja" /> Recibir notificaciones por email</label>
    </div>

    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar cambios</button>
  </form>

<?php elseif ($tab === 'disciplinas'): ?>
  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_disciplinas" />

    <div>
      <label class="block text-sm font-semibold mb-2">Disciplinas</label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($disciplines as $d): $checked = in_array((int)$d['id'], $current_disc, true); ?>
          <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
            <input type="checkbox" name="disciplines[]" value="<?= (int)$d['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
            <span><?= e($d['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <label class="block text-sm font-semibold mb-1">Especialidades</label>
      <input name="specialties" value="<?= e(implode(', ', $current_spec)) ?>" placeholder="ISO 45001, Auditoría interna…" class="w-full border border-gray-300 rounded px-3 py-2" />
      <p class="text-xs text-gris-oscuro mt-1">Separa con comas. Máx. 8 recomendadas.</p>
    </div>

    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar</button>
  </form>

<?php elseif ($tab === 'formacion'): ?>
  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5" id="form-formacion">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_formacion" />
    <div id="formacion-rows" class="space-y-4">
      <?php $rows = $formacion ?: [['degree'=>'','institution'=>'','date_from'=>'','date_to'=>'','details'=>'']];
            foreach ($rows as $i => $r): ?>
      <div class="border border-gray-200 rounded p-4 space-y-3 relative">
        <button type="button" class="absolute top-2 right-2 text-xs text-coral hover:underline" onclick="this.parentElement.remove()">Eliminar</button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div><label class="block text-xs font-semibold mb-1">Título / grado</label><input name="formacion[<?= $i ?>][degree]" value="<?= e($r['degree'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Institución</label><input name="formacion[<?= $i ?>][institution]" value="<?= e($r['institution'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Desde (año)</label><input name="formacion[<?= $i ?>][date_from]" value="<?= e($r['date_from'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Hasta (año)</label><input name="formacion[<?= $i ?>][date_to]" value="<?= e($r['date_to'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
        </div>
        <div><label class="block text-xs font-semibold mb-1">Detalles (opcional)</label><textarea name="formacion[<?= $i ?>][details]" rows="2" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($r['details'] ?? '') ?></textarea></div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="text-azul font-semibold text-sm hover:underline" onclick="addRow('formacion', ['degree','institution','date_from','date_to','details'])">+ Agregar formación</button>
    <div><button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar formación</button></div>
  </form>

<?php elseif ($tab === 'experiencia'): ?>
  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_experiencia" />
    <div id="experiencia-rows" class="space-y-4">
      <?php $rows = $experiencia ?: [['job_title'=>'','company'=>'','date_from'=>'','date_to'=>'','description'=>'']];
            foreach ($rows as $i => $r): ?>
      <div class="border border-gray-200 rounded p-4 space-y-3 relative">
        <button type="button" class="absolute top-2 right-2 text-xs text-coral hover:underline" onclick="this.parentElement.remove()">Eliminar</button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div><label class="block text-xs font-semibold mb-1">Cargo</label><input name="experiencia[<?= $i ?>][job_title]" value="<?= e($r['job_title'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Empresa</label><input name="experiencia[<?= $i ?>][company]" value="<?= e($r['company'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Desde</label><input name="experiencia[<?= $i ?>][date_from]" value="<?= e($r['date_from'] ?? '') ?>" placeholder="2020" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
          <div><label class="block text-xs font-semibold mb-1">Hasta</label><input name="experiencia[<?= $i ?>][date_to]" value="<?= e($r['date_to'] ?? '') ?>" placeholder="Presente" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
        </div>
        <div><label class="block text-xs font-semibold mb-1">Descripción</label><textarea name="experiencia[<?= $i ?>][description]" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($r['description'] ?? '') ?></textarea></div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="text-azul font-semibold text-sm hover:underline" onclick="addRow('experiencia', ['job_title','company','date_from','date_to','description'])">+ Agregar experiencia</button>
    <div><button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar experiencia</button></div>
  </form>

<?php elseif ($tab === 'servicios'): ?>
  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <input type="hidden" name="action" value="save_servicios" />
    <p class="text-sm text-gris-oscuro">Define los servicios que ofreces. Aparecen en tu perfil público y los profesionales pueden además publicarlos en la <a href="<?= e(u('/bolsa')) ?>" class="text-azul hover:underline">Bolsa de Trabajo</a>.</p>
    <div id="servicios-rows" class="space-y-4">
      <?php $rows = $servicios ?: [['title'=>'','description'=>'']];
            foreach ($rows as $i => $r): ?>
      <div class="border border-gray-200 rounded p-4 space-y-3 relative">
        <button type="button" class="absolute top-2 right-2 text-xs text-coral hover:underline" onclick="this.parentElement.remove()">Eliminar</button>
        <div><label class="block text-xs font-semibold mb-1">Título del servicio</label><input name="servicios[<?= $i ?>][title]" value="<?= e($r['title'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" /></div>
        <div><label class="block text-xs font-semibold mb-1">Descripción</label><textarea name="servicios[<?= $i ?>][description]" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($r['description'] ?? '') ?></textarea></div>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="text-azul font-semibold text-sm hover:underline" onclick="addRow('servicios', ['title','description'])">+ Agregar servicio</button>
    <div><button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Guardar servicios</button></div>
  </form>

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
      <p class="text-xs text-gris-oscuro mt-1">Mínimo 8 caracteres.</p>
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Confirmar nueva contraseña</label>
      <input name="new_password_confirm" type="password" required minlength="8" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>
    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Actualizar contraseña</button>
  </form>
<?php endif; ?>
</section>

<script>
// Agrega filas dinámicas para listas (formación, experiencia, servicios)
function addRow(key, fields) {
  const container = document.getElementById(key + '-rows');
  if (!container) return;
  const idx = container.children.length;
  const wrap = document.createElement('div');
  wrap.className = 'border border-gray-200 rounded p-4 space-y-3 relative';
  let html = '<button type="button" class="absolute top-2 right-2 text-xs text-coral hover:underline" onclick="this.parentElement.remove()">Eliminar</button>';
  for (const f of fields) {
    const isLong = f === 'description' || f === 'details';
    html += `<div><label class="block text-xs font-semibold mb-1">${f}</label>${isLong
      ? `<textarea name="${key}[${idx}][${f}]" rows="2" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>`
      : `<input name="${key}[${idx}][${f}]" class="w-full border border-gray-300 rounded px-3 py-2" />`
    }</div>`;
  }
  wrap.innerHTML = html;
  container.appendChild(wrap);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
