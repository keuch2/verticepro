<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Session is needed for CSRF + flash.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$old = [
    'name' => '', 'email' => '', 'title' => '', 'city_id' => '', 'type_id' => '',
    'disciplines' => [], 'specialties' => '', 'bio' => '', 'linkedin' => '', 'website' => '', 'phone' => '',
    'password' => '', 'password_confirm' => '',
    'visibility_email' => 1, 'visibility_linkedin' => 1, 'visibility_website' => 1, 'visibility_phone' => 0,
    'notifications_opt_in' => 1, 'accept_terms' => 0,
];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['name']        = trim($_POST['name'] ?? '');
    $old['email']       = trim($_POST['email'] ?? '');
    $old['title']       = trim($_POST['title'] ?? '');
    $old['city_id']     = $_POST['city_id'] ?? '';
    $old['type_id']     = $_POST['type_id'] ?? '';
    $old['disciplines'] = array_map('intval', (array)($_POST['disciplines'] ?? []));
    $old['specialties'] = trim($_POST['specialties'] ?? '');
    $old['bio']         = trim($_POST['bio'] ?? '');
    $old['linkedin']    = trim($_POST['linkedin'] ?? '');
    $old['website']     = trim($_POST['website'] ?? '');
    $old['phone']       = trim($_POST['phone'] ?? '');
    $old['password']         = (string)($_POST['password'] ?? '');
    $old['password_confirm'] = (string)($_POST['password_confirm'] ?? '');
    $old['visibility_email']    = !empty($_POST['visibility_email'])    ? 1 : 0;
    $old['visibility_linkedin'] = !empty($_POST['visibility_linkedin']) ? 1 : 0;
    $old['visibility_website']  = !empty($_POST['visibility_website'])  ? 1 : 0;
    $old['visibility_phone']    = !empty($_POST['visibility_phone'])    ? 1 : 0;
    $old['notifications_opt_in'] = !empty($_POST['notifications_opt_in']) ? 1 : 0;
    $old['accept_terms']        = !empty($_POST['accept_terms']) ? 1 : 0;

    if (!$old['accept_terms'])                                $errors['accept_terms'] = 'Debes aceptar los términos y condiciones para continuar.';
    if (strlen($old['password']) < 8)                         $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    elseif ($old['password'] !== $old['password_confirm'])    $errors['password'] = 'Las contraseñas no coinciden.';
    if ($old['name'] === '')                                  $errors['name']  = 'Indícanos tu nombre completo.';
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Necesitamos un email válido para contactarte.';
    if ($old['title'] === '')                                 $errors['title'] = 'Indica tu título o cargo profesional.';
    if (empty($old['disciplines']))                           $errors['disciplines'] = 'Selecciona al menos una disciplina.';
    if ($old['bio'] !== '' && mb_strlen($old['bio']) > 2000)  $errors['bio'] = 'La bio no puede superar los 2000 caracteres.';
    if ($old['linkedin'] !== '' && !preg_match('#^https?://#i', $old['linkedin'])) $errors['linkedin'] = 'Debe empezar con http(s)://';
    if ($old['website']  !== '' && !preg_match('#^https?://#i', $old['website']))  $errors['website']  = 'Debe empezar con http(s)://';

    // Prevent duplicate registrations by email (already pending or active).
    if (!isset($errors['email'])) {
        $exists = DB::one('SELECT id, status FROM professionals WHERE email = ? LIMIT 1', [$old['email']]);
        if ($exists) {
            $errors['email'] = $exists['status'] === 'pending'
                ? 'Ya tenemos una solicitud pendiente con este email. La estamos revisando.'
                : 'Este email ya está registrado en la red. Si es tu cuenta, inicia sesión o recupera tu contraseña.';
        } elseif (DB::one('SELECT id FROM users WHERE email = ? LIMIT 1', [$old['email']])) {
            $errors['email'] = 'Este email ya tiene una cuenta. Inicia sesión o recupera tu contraseña.';
        }
    }

    if (!$errors) {
        // Build a unique slug from name.
        $base = slugify($old['name']) ?: 'profesional';
        $slug = $base;
        $i = 2;
        while (DB::one('SELECT id FROM professionals WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }

        // Crear usuario con rol professional (status=pending; se activa al aprobar el perfil).
        $user_id = DB::insert('users', [
            'email' => $old['email'],
            'password_hash' => password_hash($old['password'], PASSWORD_DEFAULT),
            'role' => 'professional',
            'name' => $old['name'],
            'status' => 'pending',
            'notifications_opt_in' => $old['notifications_opt_in'],
        ]);

        $id = DB::insert('professionals', [
            'user_id'  => $user_id,
            'slug'     => $slug,
            'name'     => $old['name'],
            'title'    => $old['title'],
            'bio'      => $old['bio'] ?: null,
            'city_id'  => $old['city_id'] !== '' ? (int)$old['city_id'] : null,
            'type_id'  => $old['type_id'] !== '' ? (int)$old['type_id'] : null,
            'email'    => $old['email'],
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

        foreach ($old['disciplines'] as $did) {
            if ($did > 0) {
                try { DB::insert('professional_disciplines', ['professional_id' => $id, 'discipline_id' => $did]); } catch (\Throwable $e) {}
            }
        }
        foreach (array_filter(array_map('trim', explode(',', $old['specialties']))) as $s) {
            try { DB::insert('professional_specialties', ['professional_id' => $id, 'specialty' => $s]); } catch (\Throwable $e) {}
        }

        $submitted_ok = true;
        $old = [
            'name'=>'','email'=>'','title'=>'','city_id'=>'','type_id'=>'','disciplines'=>[],
            'specialties'=>'','bio'=>'','linkedin'=>'','website'=>'','phone'=>'',
            'password'=>'','password_confirm'=>'',
            'visibility_email'=>1,'visibility_linkedin'=>1,'visibility_website'=>1,'visibility_phone'=>0,
            'notifications_opt_in'=>1,'accept_terms'=>0,
        ];
    }
}

$cities      = SectionRepo::cities();
$types       = SectionRepo::profTypes();
$disciplines = SectionRepo::disciplines();

$page_title = 'Únete a la Red — Vértice Pro';
$page_active = 'registro.php';
$page_description = 'Crea tu perfil profesional en la Red Vértice Pro: visibilidad ante empresas, colegas y oportunidades en Iberoamérica.';
include __DIR__ . '/includes/header.php';
?>
  <section class="relative px-6 py-14" style="background: linear-gradient(135deg, rgba(0,120,212,0.92), rgba(24,50,70,0.85)); color:#fff;">
    <div class="max-w-4xl mx-auto">
      <p class="uppercase tracking-wide text-xs font-bold opacity-80">Red de Profesionales</p>
      <h1 class="text-4xl font-extrabold mt-2">Únete a la Red Vértice Pro</h1>
      <p class="text-lg mt-3 opacity-90 max-w-2xl">Crea tu perfil profesional y conecta con consultores, auditores, empresas y oportunidades en calidad, seguridad, salud ocupacional y medio ambiente.</p>
      <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 text-sm">
        <li class="bg-white/10 rounded px-4 py-2">✓ Perfil verificable</li>
        <li class="bg-white/10 rounded px-4 py-2">✓ Visibilidad ante empresas</li>
        <li class="bg-white/10 rounded px-4 py-2">✓ Acceso a la bolsa de trabajo</li>
      </ul>
    </div>
  </section>

  <section class="max-w-4xl mx-auto px-6 py-14">
    <?php if ($submitted_ok): ?>
      <div class="bg-verde/10 border border-verde rounded-lg p-6 text-texto">
        <h2 class="text-xl font-extrabold text-verde">¡Solicitud recibida!</h2>
        <p class="mt-2 text-gris-oscuro">Gracias por sumarte a la Red Vértice Pro. Tu perfil quedó <strong>pendiente de aprobación</strong>: nuestro equipo lo revisará en las próximas 24–48 horas y te avisaremos por email cuando esté publicado en el directorio.</p>
        <div class="mt-5 flex flex-wrap gap-3">
          <a href="<?= e(u('/directorio')) ?>" class="bg-azul text-white font-semibold px-5 py-2 rounded hover:bg-blue-700 transition">Explorar el directorio</a>
          <a href="<?= e(u('/red')) ?>" class="border border-gris-oscuro text-gris-oscuro font-semibold px-5 py-2 rounded hover:bg-gris-claro transition">Volver a la red</a>
        </div>
      </div>
    <?php else: ?>
      <div class="mb-8">
        <h2 class="text-2xl font-extrabold">Registro profesional</h2>
        <p class="text-gris-oscuro mt-1">Completa el formulario. Todos los perfiles pasan por una revisión antes de aparecer en el directorio público.</p>
      </div>

      <?php if ($errors): ?>
        <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mb-6 text-sm">
          <p class="font-semibold mb-1">Revisa los campos marcados:</p>
          <ul class="list-disc pl-5">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate class="bg-white border border-gray-200 rounded-lg p-6 space-y-5">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Nombre completo <span class="text-coral">*</span></label>
            <input name="name" required value="<?= e($old['name']) ?>" class="w-full border <?= isset($errors['name'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Email de contacto <span class="text-coral">*</span></label>
            <input name="email" type="email" required value="<?= e($old['email']) ?>" class="w-full border <?= isset($errors['email'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Contraseña <span class="text-coral">*</span></label>
            <input name="password" type="password" required minlength="8" class="w-full border <?= isset($errors['password'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
            <p class="text-xs text-gris-oscuro mt-1">Mínimo 8 caracteres. La usarás para editar tu perfil.</p>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Confirmar contraseña <span class="text-coral">*</span></label>
            <input name="password_confirm" type="password" required minlength="8" class="w-full border <?= isset($errors['password'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">Título profesional / cargo <span class="text-coral">*</span></label>
          <input name="title" required value="<?= e($old['title']) ?>" placeholder="Ej: Consultora senior en gestión de SSL" class="w-full border <?= isset($errors['title'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Ciudad</label>
            <select name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (string)$old['city_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?><?= $c['country_name'] ? ', ' . e($c['country_name']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Tipo de profesional</label>
            <select name="type_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach ($types as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (string)$old['type_id'] === (string)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-2">Disciplinas <span class="text-coral">*</span></label>
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
          <input name="specialties" value="<?= e($old['specialties']) ?>" placeholder="ISO 45001, Auditoría interna, Análisis de riesgos…" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          <p class="text-xs text-gris-oscuro mt-1">Separa con comas (máx. 8 recomendadas).</p>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">Bio profesional</label>
          <textarea name="bio" rows="5" maxlength="2000" placeholder="Cuéntanos sobre tu trayectoria, sectores de experiencia, certificaciones…" class="w-full border <?= isset($errors['bio'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none"><?= e($old['bio']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">LinkedIn</label>
            <input name="linkedin" value="<?= e($old['linkedin']) ?>" placeholder="https://linkedin.com/in/…" class="w-full border <?= isset($errors['linkedin'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Sitio web</label>
            <input name="website" value="<?= e($old['website']) ?>" placeholder="https://…" class="w-full border <?= isset($errors['website'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Teléfono / WhatsApp</label>
            <input name="phone" value="<?= e($old['phone']) ?>" placeholder="+595 9XX XXX XXX" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
        </div>

        <div class="border border-gray-200 rounded p-4 space-y-2">
          <p class="font-semibold text-sm">¿Qué datos quieres mostrar públicamente en tu perfil?</p>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= $old['visibility_email'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi email</label>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_linkedin" value="1" <?= $old['visibility_linkedin'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi LinkedIn</label>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= $old['visibility_website'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi sitio web</label>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_phone" value="1" <?= $old['visibility_phone'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar mi teléfono / WhatsApp</label>
          <p class="text-xs text-gris-oscuro opacity-80">Los datos no visibles seguirán siendo usados internamente para verificación, pero no se mostrarán en tu perfil público.</p>
        </div>

        <div class="border border-gray-200 rounded p-4">
          <label class="flex items-start gap-3 text-sm text-gris-oscuro">
            <input type="checkbox" name="notifications_opt_in" value="1" <?= $old['notifications_opt_in'] ? 'checked' : '' ?> class="accent-naranja mt-0.5" />
            <span>Quiero recibir notificaciones por email sobre actividad relevante de mi perfil (aprobación, interesados en mis servicios, mensajes, eventos).</span>
          </label>
        </div>

        <div class="bg-gris-claro rounded p-4 text-sm text-gris-oscuro space-y-3">
          <p>Vértice Pro se reserva el derecho de modificar los <a href="<?= e(u('/terminos')) ?>" class="text-azul hover:underline">términos y condiciones</a> del servicio en cualquier momento. Los cambios serán notificados a los usuarios registrados con razonable antelación.</p>
          <p>Al enviar, aceptas que revisemos tu información antes de publicarla. Consulta nuestra <a href="<?= e(u('/privacidad')) ?>" class="text-azul hover:underline">política de privacidad</a>.</p>
          <label class="flex items-start gap-3 mt-2">
            <input type="checkbox" name="accept_terms" value="1" <?= $old['accept_terms'] ? 'checked' : '' ?> class="accent-naranja mt-0.5" required />
            <span>He leído y acepto los <a href="<?= e(u('/terminos')) ?>" class="text-azul hover:underline">términos y condiciones</a> y la <a href="<?= e(u('/privacidad')) ?>" class="text-azul hover:underline">política de privacidad</a>. <span class="text-coral">*</span></span>
          </label>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
          <button type="submit" class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition">Enviar solicitud</button>
          <a href="<?= e(u('/red')) ?>" class="text-gris-oscuro hover:text-naranja transition text-sm">Cancelar</a>
        </div>
      </form>
    <?php endif; ?>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
