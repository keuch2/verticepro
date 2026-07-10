<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
require_once __DIR__ . '/includes/image.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$u = require_public_user();
$already = DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
if ($already) { redirect('/mi-perfil'); }

$errors = [];
$old = [
    'name' => $u['name'] ?? '', 'title' => '', 'city_id' => '',
    'types' => [], 'disciplines' => [],
    'specialties' => '', 'bio' => '',
    'linkedin' => '', 'website' => '', 'phone' => '',
    'visibility_email' => 1, 'visibility_linkedin' => 1, 'visibility_website' => 1, 'visibility_phone' => 0,
    'notifications_opt_in' => 1,
];
$submitted_ok = false;

// Datos de referencia: antes del handler POST para validar FKs server-side (whitelist).
$cities      = SectionRepo::cities();
$types       = SectionRepo::profTypes();
$disciplines = SectionRepo::disciplines();
$valid_city_ids       = array_map(fn($r) => (int)$r['id'], $cities);
$valid_type_ids       = array_map(fn($r) => (int)$r['id'], $types);
$valid_discipline_ids = array_map(fn($r) => (int)$r['id'], $disciplines);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['name','title','specialties','bio','linkedin','website','phone'] as $k) $old[$k] = trim($_POST[$k] ?? '');
    $old['city_id']     = $_POST['city_id'] ?? '';
    $old['types']       = array_map('intval', (array)($_POST['types'] ?? []));
    $old['disciplines'] = array_map('intval', (array)($_POST['disciplines'] ?? []));
    foreach (['visibility_email','visibility_linkedin','visibility_website','visibility_phone','notifications_opt_in'] as $k) {
        $old[$k] = !empty($_POST[$k]) ? 1 : 0;
    }

    if ($old['name'] === '')           $errors['name'] = 'Indica tu nombre completo.';
    elseif (mb_strlen($old['name']) > 150) $errors['name'] = 'El nombre no puede superar los 150 caracteres.';
    if ($old['title'] === '')          $errors['title'] = 'Indica tu título o cargo.';
    elseif (mb_strlen($old['title']) > 200) $errors['title'] = 'El título no puede superar los 200 caracteres.';
    if (empty($old['disciplines']))    $errors['disciplines'] = 'Selecciona al menos una disciplina.';
    if ($old['linkedin'] !== '' && !preg_match('#^https?://#i', $old['linkedin'])) $errors['linkedin'] = 'Debe empezar con http(s)://';
    if ($old['website']  !== '' && !preg_match('#^https?://#i', $old['website']))  $errors['website']  = 'Debe empezar con http(s)://';

    // Validar server-side que las FK existan (whitelist).
    if ($old['city_id'] !== '' && !in_array((int)$old['city_id'], $valid_city_ids, true)) {
        $errors['city_id'] = 'La ciudad seleccionada no es válida.';
    }
    $old['types']       = array_values(array_intersect($old['types'], $valid_type_ids));
    $old['disciplines'] = array_values(array_intersect($old['disciplines'], $valid_discipline_ids));
    if (!empty($_POST['disciplines']) && empty($old['disciplines'])) {
        $errors['disciplines'] = 'Las disciplinas seleccionadas no son válidas.';
    }

    if (!$errors) {
        $base = slugify($old['name']) ?: 'profesional';
        $slug = $base; $i = 2;
        while (DB::one('SELECT id FROM professionals WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }

        $avatar = null;
        if (!empty($_FILES['avatar']['name'])) {
            $rel = upload_image($_FILES['avatar'], 'profiles', $slug);
            if ($rel) $avatar = $rel;
        }

        $primary_type = !empty($old['types']) ? (int)$old['types'][0] : null;

        // professional + relaciones M:N en UNA transacción: si algo falla, rollBack
        // completo (no queda un perfil a medias sin disciplinas) y mensaje legible.
        try {
            DB::transaction(function () use ($u, $old, $slug, $avatar, $primary_type) {
                $pid = DB::insert('professionals', [
                    'user_id'  => (int)$u['id'],
                    'slug'     => $slug,
                    'name'     => $old['name'],
                    'title'    => $old['title'],
                    'bio'      => $old['bio'] ?: null,
                    'city_id'  => $old['city_id'] !== '' ? (int)$old['city_id'] : null,
                    'type_id'  => $primary_type,
                    'avatar_image' => $avatar,
                    'email'    => $u['email'],
                    'linkedin' => $old['linkedin'] ?: null,
                    'website'  => $old['website']  ?: null,
                    'phone'    => $old['phone']    ?: null,
                    'verified' => 0,
                    'available'=> 1,
                    'featured' => 0,
                    'visibility_email'    => $old['visibility_email'],
                    'visibility_linkedin' => $old['visibility_linkedin'],
                    'visibility_website'  => $old['visibility_website'],
                    'visibility_phone'    => $old['visibility_phone'],
                    'notifications_opt_in'=> $old['notifications_opt_in'],
                    'status'   => 'pending',
                ]);

                if (!empty($old['types'])) ProfessionalRepo::setTypes($pid, $old['types']);
                foreach ($old['disciplines'] as $did) {
                    if ($did > 0) DB::insert('professional_disciplines', ['professional_id' => $pid, 'discipline_id' => $did]);
                }
                foreach (array_filter(array_map('trim', explode(',', $old['specialties']))) as $s) {
                    DB::insert('professional_specialties', ['professional_id' => $pid, 'specialty' => mb_substr($s, 0, 100)]);
                }
            });
            $submitted_ok = true;
        } catch (\Throwable $e) {
            error_log('[crear-perfil.php] fallo al crear perfil: ' . $e->getMessage());
            $errors['general'] = 'No pudimos crear tu perfil por un problema técnico. Por favor inténtalo de nuevo.';
        }
    }
}

$page_title = 'Crear mi perfil profesional — Vértice Pro';
$page_active = 'crear-perfil.php';
include __DIR__ . '/includes/header.php';
?>
<section class="bg-gris-claro py-8 px-6">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-extrabold">Crear mi perfil profesional</h1>
    <p class="text-gris-oscuro text-sm mt-1">Como <?= e($u['name']) ?>, vas a sumar un perfil profesional vinculado a tu cuenta actual.</p>
  </div>
</section>

<section class="max-w-4xl mx-auto px-6 py-10">
  <?php if ($submitted_ok): ?>
    <div class="bg-verde/10 border border-verde rounded-lg p-6">
      <h2 class="text-xl font-extrabold text-verde">¡Solicitud recibida!</h2>
      <p class="mt-2 text-gris-oscuro">Tu perfil profesional quedó <strong>pendiente de aprobación</strong>. Cuando se publique podrás administrarlo desde <a href="<?= e(u('/mi-perfil')) ?>" class="text-azul hover:underline">Mi perfil</a>.</p>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mb-6 text-sm">
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">Nombre completo *</label>
          <input name="name" required value="<?= e($old['name']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Título profesional *</label>
          <input name="title" required value="<?= e($old['title']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">Ciudad</label>
          <select name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">— Selecciona —</option>
            <?php foreach ($cities as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?><?= $c['country_name'] ? ', ' . e($c['country_name']) : '' ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Foto de perfil</label>
          <input type="file" name="avatar" accept="image/*" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Tipo(s) de profesional</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <?php foreach ($types as $t): $checked = in_array((int)$t['id'], $old['types'], true); ?>
            <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
              <input type="checkbox" name="types[]" value="<?= (int)$t['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
              <span><?= e($t['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2">Disciplinas *</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <?php foreach ($disciplines as $d): $checked = in_array((int)$d['id'], $old['disciplines'], true); ?>
            <label class="flex items-center gap-2 border <?= $checked ? 'border-naranja bg-naranja/5' : 'border-gray-200' ?> rounded px-3 py-2 cursor-pointer hover:border-naranja transition text-sm">
              <input type="checkbox" name="disciplines[]" value="<?= (int)$d['id'] ?>" <?= $checked ? 'checked' : '' ?> class="accent-naranja" />
              <span><?= e($d['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Especialidades</label>
        <input name="specialties" value="<?= e($old['specialties']) ?>" placeholder="ISO 45001, Auditoría interna…" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Bio</label>
        <textarea name="bio" rows="4" maxlength="2000" class="w-full border border-gray-300 rounded px-3 py-2"><?= e($old['bio']) ?></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">LinkedIn</label>
          <input name="linkedin" value="<?= e($old['linkedin']) ?>" placeholder="https://linkedin.com/in/..." class="w-full border border-gray-300 rounded px-3 py-2" />
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
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= $old['visibility_email'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi email</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_linkedin" value="1" <?= $old['visibility_linkedin'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar LinkedIn</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= $old['visibility_website'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar sitio web</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_phone" value="1" <?= $old['visibility_phone'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar teléfono</label>
        <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="notifications_opt_in" value="1" <?= $old['notifications_opt_in'] ? 'checked' : '' ?> class="accent-naranja" /> Recibir notificaciones por email</label>
      </div>

      <div class="flex gap-3">
        <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Enviar para revisión</button>
        <a href="<?= e(u('/mi-organizacion')) ?>" class="text-gris-oscuro hover:text-naranja text-sm py-2.5">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
