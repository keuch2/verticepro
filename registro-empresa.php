<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$old = [
    'name' => '', 'email' => '', 'description' => '',
    'sector_id' => '', 'country_id' => '', 'department_id' => '', 'city_id' => '',
    'size' => '', 'founded_year' => '', 'website' => '',
    'visibility_email' => 1, 'visibility_website' => 1,
    'notifications_opt_in' => 1, 'accept_terms' => 0,
];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old['name']         = trim($_POST['name'] ?? '');
    $old['email']        = trim($_POST['email'] ?? '');
    $old['description']  = trim($_POST['description'] ?? '');
    $old['sector_id']    = $_POST['sector_id'] ?? '';
    $old['country_id']   = $_POST['country_id'] ?? '';
    $old['department_id']= $_POST['department_id'] ?? '';
    $old['city_id']      = $_POST['city_id'] ?? '';
    $old['size']         = $_POST['size'] ?? '';
    $old['founded_year'] = trim($_POST['founded_year'] ?? '');
    $old['website']      = trim($_POST['website'] ?? '');
    $old['visibility_email']    = !empty($_POST['visibility_email']) ? 1 : 0;
    $old['visibility_website']  = !empty($_POST['visibility_website']) ? 1 : 0;
    $old['notifications_opt_in']= !empty($_POST['notifications_opt_in']) ? 1 : 0;
    $old['accept_terms']        = !empty($_POST['accept_terms']) ? 1 : 0;

    if (!$old['accept_terms'])                                $errors['accept_terms'] = 'Debes aceptar los términos y condiciones para continuar.';
    if ($old['name'] === '')                                  $errors['name'] = 'Indícanos el nombre de la empresa.';
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Necesitamos un email válido para contactarte.';
    if ($old['sector_id'] === '')                             $errors['sector_id'] = 'Selecciona un sector.';
    if ($old['country_id'] === '')                            $errors['country_id'] = 'Selecciona un país.';
    if ($old['description'] !== '' && mb_strlen($old['description']) > 2000) $errors['description'] = 'La descripción no puede superar los 2000 caracteres.';
    if ($old['website'] !== '' && !preg_match('#^https?://#i', $old['website'])) $errors['website'] = 'Debe empezar con http(s)://';
    if ($old['founded_year'] !== '' && (!ctype_digit($old['founded_year']) || (int)$old['founded_year'] < 1800 || (int)$old['founded_year'] > (int)date('Y'))) {
        $errors['founded_year'] = 'Año inválido.';
    }

    // Duplicate email
    if (!isset($errors['email'])) {
        $exists = DB::one('SELECT id, status FROM companies WHERE email = ? LIMIT 1', [$old['email']]);
        if ($exists) {
            $errors['email'] = $exists['status'] === 'pending'
                ? 'Ya tenemos una solicitud pendiente con este email. La estamos revisando.'
                : 'Este email ya está registrado para otra empresa.';
        }
    }

    if (!$errors) {
        $base = slugify($old['name']) ?: 'empresa';
        $slug = $base; $i = 2;
        while (DB::one('SELECT id FROM companies WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }

        DB::insert('companies', [
            'slug'         => $slug,
            'name'         => $old['name'],
            'description'  => $old['description'] ?: null,
            'sector_id'    => (int)$old['sector_id'],
            'country_id'   => (int)$old['country_id'],
            'city_id'      => $old['city_id'] !== '' ? (int)$old['city_id'] : null,
            'size'         => $old['size'] ?: null,
            'founded_year' => $old['founded_year'] !== '' ? (int)$old['founded_year'] : null,
            'website'      => $old['website'] ?: null,
            'email'        => $old['email'],
            'verified'     => 0,
            'visibility_email'    => $old['visibility_email'],
            'visibility_website'  => $old['visibility_website'],
            'notifications_opt_in'=> $old['notifications_opt_in'],
            'status'       => 'pending',
        ]);

        $submitted_ok = true;
        $old = [
            'name'=>'','email'=>'','description'=>'','sector_id'=>'','country_id'=>'',
            'department_id'=>'','city_id'=>'','size'=>'','founded_year'=>'','website'=>'',
            'visibility_email'=>1,'visibility_website'=>1,
            'notifications_opt_in'=>1,'accept_terms'=>0,
        ];
    }
}

$sectors     = SectionRepo::sectors();
$countries   = SectionRepo::countries();
$departments = SectionRepo::departments();
$cities      = SectionRepo::cities();

// Paraguay preseleccionado por defecto
$default_country_id = (int)(DB::one("SELECT id FROM countries WHERE slug = 'paraguay'")['id'] ?? 0);
$selected_country = $old['country_id'] !== '' ? (int)$old['country_id'] : $default_country_id;

$page_title = 'Registra tu empresa — Vértice Pro';
$page_active = 'registro-empresa.php';
$page_description = 'Crea el perfil público de tu empresa en Vértice Pro y conecta con profesionales y oportunidades en Paraguay.';
include __DIR__ . '/includes/header.php';
?>
  <section class="relative px-6 py-14" style="background: linear-gradient(135deg, rgba(245,130,32,0.92), rgba(180,80,10,0.85)); color:#fff;">
    <div class="max-w-4xl mx-auto">
      <p class="uppercase tracking-wide text-xs font-bold opacity-80">Empresas</p>
      <h1 class="text-4xl font-extrabold mt-2">Registra tu empresa</h1>
      <p class="text-lg mt-3 opacity-90 max-w-2xl">Suma tu empresa al directorio de Vértice Pro. Conecta con profesionales del sector y publica ofertas en la Bolsa de Trabajo.</p>
      <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 text-sm">
        <li class="bg-white/10 rounded px-4 py-2">✓ Perfil público verificable</li>
        <li class="bg-white/10 rounded px-4 py-2">✓ Visibilidad ante profesionales</li>
        <li class="bg-white/10 rounded px-4 py-2">✓ Acceso a la Bolsa de Trabajo</li>
      </ul>
    </div>
  </section>

  <section class="max-w-4xl mx-auto px-6 py-14">
    <?php if ($submitted_ok): ?>
      <div class="bg-verde/10 border border-verde rounded-lg p-6 text-texto">
        <h2 class="text-xl font-extrabold text-verde">¡Solicitud recibida!</h2>
        <p class="mt-2 text-gris-oscuro">Gracias por sumar tu empresa a Vértice Pro. Tu solicitud quedó <strong>pendiente de aprobación</strong>: nuestro equipo la revisará en las próximas 24–48 horas y te avisaremos por email cuando esté publicada.</p>
        <div class="mt-5 flex flex-wrap gap-3">
          <a href="<?= e(u('/empresas')) ?>" class="bg-azul text-white font-semibold px-5 py-2 rounded hover:bg-blue-700 transition">Ver el directorio</a>
          <a href="<?= e(u('/')) ?>" class="border border-gris-oscuro text-gris-oscuro font-semibold px-5 py-2 rounded hover:bg-gris-claro transition">Volver al inicio</a>
        </div>
      </div>
    <?php else: ?>
      <div class="mb-8">
        <h2 class="text-2xl font-extrabold">Registro de empresa</h2>
        <p class="text-gris-oscuro mt-1">Completa el formulario. Todas las empresas pasan por una revisión antes de aparecer en el directorio público.</p>
      </div>

      <?php if ($errors): ?>
        <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mb-6 text-sm">
          <p class="font-semibold mb-1">Revisa los campos marcados:</p>
          <ul class="list-disc pl-5">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate class="bg-white border border-gray-200 rounded-lg p-6 space-y-5" id="registro-empresa-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Nombre de la empresa <span class="text-coral">*</span></label>
            <input name="name" required value="<?= e($old['name']) ?>" class="w-full border <?= isset($errors['name'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Email de contacto <span class="text-coral">*</span></label>
            <input name="email" type="email" required value="<?= e($old['email']) ?>" class="w-full border <?= isset($errors['email'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Sector <span class="text-coral">*</span></label>
            <select name="sector_id" required class="w-full border <?= isset($errors['sector_id'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach ($sectors as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (string)$old['sector_id'] === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Tamaño</label>
            <select name="size" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach (['1-10','11-50','51-200','200+'] as $sz): ?>
                <option value="<?= e($sz) ?>" <?= $old['size'] === $sz ? 'selected' : '' ?>><?= e($sz) ?> empleados</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">País <span class="text-coral">*</span></label>
            <select id="country-select" name="country_id" required class="w-full border <?= isset($errors['country_id'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 bg-white">
              <?php foreach ($countries as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$selected_country === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Departamento</label>
            <select id="department-select" name="department_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>" data-country="<?= (int)$d['country_id'] ?>" <?= (string)$old['department_id'] === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Ciudad</label>
            <select id="city-select" name="city_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
              <option value="">— Selecciona —</option>
              <?php foreach ($cities as $c): ?>
                <option value="<?= (int)$c['id'] ?>" data-country="<?= (int)$c['country_id'] ?>" data-department="<?= (int)($c['department_id'] ?? 0) ?>" <?= (string)$old['city_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold mb-1">Descripción</label>
          <textarea name="description" rows="5" maxlength="2000" placeholder="Cuéntanos sobre la empresa, servicios, sectores de cliente…" class="w-full border <?= isset($errors['description'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none"><?= e($old['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold mb-1">Año de fundación</label>
            <input name="founded_year" value="<?= e($old['founded_year']) ?>" placeholder="2010" class="w-full border <?= isset($errors['founded_year'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Sitio web</label>
            <input name="website" value="<?= e($old['website']) ?>" placeholder="https://…" class="w-full border <?= isset($errors['website'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 focus:border-naranja focus:ring-1 focus:ring-naranja outline-none" />
          </div>
        </div>

        <div class="border border-gray-200 rounded p-4 space-y-2">
          <p class="font-semibold text-sm">¿Qué datos quieres mostrar públicamente?</p>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_email" value="1" <?= $old['visibility_email'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar email de contacto</label>
          <label class="flex items-center gap-2 text-sm text-gris-oscuro"><input type="checkbox" name="visibility_website" value="1" <?= $old['visibility_website'] ? 'checked' : '' ?> class="accent-naranja" /> Mostrar sitio web</label>
        </div>

        <div class="border border-gray-200 rounded p-4">
          <label class="flex items-start gap-3 text-sm text-gris-oscuro">
            <input type="checkbox" name="notifications_opt_in" value="1" <?= $old['notifications_opt_in'] ? 'checked' : '' ?> class="accent-naranja mt-0.5" />
            <span>Quiero recibir notificaciones por email sobre actividad relevante (aprobación, interesados en ofertas, mensajes).</span>
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
          <a href="<?= e(u('/empresas')) ?>" class="text-gris-oscuro hover:text-naranja transition text-sm">Cancelar</a>
        </div>
      </form>

      <script>
        // Cascading selects: País → Departamento → Ciudad
        (function() {
          const country = document.getElementById('country-select');
          const dept    = document.getElementById('department-select');
          const city    = document.getElementById('city-select');
          if (!country || !dept || !city) return;
          function sync() {
            const c = country.value, d = dept.value;
            Array.from(dept.options).forEach(o => {
              if (!o.value) return;
              o.hidden = o.dataset.country !== c;
              if (o.hidden && o.selected) { dept.value = ''; }
            });
            Array.from(city.options).forEach(o => {
              if (!o.value) return;
              const matchCountry = o.dataset.country === c;
              const matchDept = !d || o.dataset.department === d;
              o.hidden = !(matchCountry && matchDept);
              if (o.hidden && o.selected) { city.value = ''; }
            });
          }
          country.addEventListener('change', sync);
          dept.addEventListener('change', sync);
          sync();
        })();
      </script>
    <?php endif; ?>
  </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
