<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$old = [
    'company_email' => '', 'title' => '', 'description' => '',
    'category' => '', 'modality' => '', 'country_id' => '',
    'salary_min' => '', 'salary_max' => '',
];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if ($old['company_email'] === '' || !filter_var($old['company_email'], FILTER_VALIDATE_EMAIL)) $errors['company_email'] = 'Indica el email registrado de la empresa.';
    if ($old['title'] === '')                                  $errors['title'] = 'Indica un título.';
    if ($old['description'] === '')                            $errors['description'] = 'Describe la oferta.';
    if ($old['modality'] === '')                               $errors['modality'] = 'Selecciona modalidad.';

    $company = null;
    if (!isset($errors['company_email'])) {
        $company = DB::one('SELECT id, name, status FROM companies WHERE email = ? LIMIT 1', [$old['company_email']]);
        if (!$company) {
            $errors['company_email'] = 'No encontramos una empresa registrada con ese email. Regístrala primero.';
        } elseif ($company['status'] !== 'active') {
            $errors['company_email'] = 'La empresa todavía no está aprobada. Espera la confirmación de Vértice Pro.';
        }
    }

    if (!$errors) {
        $base = slugify($old['title']) ?: 'oferta';
        $slug = $base; $i = 2;
        while (DB::one('SELECT id FROM job_offers WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }
        DB::insert('job_offers', [
            'company_id'   => (int)$company['id'],
            'slug'         => $slug,
            'title'        => $old['title'],
            'description'  => $old['description'],
            'category'     => $old['category'] ?: null,
            'modality'     => $old['modality'] ?: null,
            'country_id'   => $old['country_id'] !== '' ? (int)$old['country_id'] : null,
            'salary_min'   => $old['salary_min'] !== '' ? (int)$old['salary_min'] : null,
            'salary_max'   => $old['salary_max'] !== '' ? (int)$old['salary_max'] : null,
            'status'       => 'draft',
        ]);
        $submitted_ok = true;
        $old = ['company_email'=>'','title'=>'','description'=>'','category'=>'','modality'=>'','country_id'=>'','salary_min'=>'','salary_max'=>''];
    }
}

$countries = SectionRepo::countries();
$default_country_id = (int)(DB::one("SELECT id FROM countries WHERE slug = 'paraguay'")['id'] ?? 0);
$selected_country = $old['country_id'] !== '' ? (int)$old['country_id'] : $default_country_id;

$page_title = 'Publicar oferta de empleo — Vértice Pro';
$page_active = 'bolsa-publicar-oferta.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-6 py-14">
  <h1 class="text-3xl font-extrabold">Publicar oferta de empleo</h1>
  <p class="text-gris-oscuro mt-2">La publicación es gratuita. Tu oferta quedará en revisión y la publicaremos en menos de 48 horas.</p>

  <?php if ($submitted_ok): ?>
    <div class="bg-verde/10 border border-verde rounded-lg p-6 mt-6">
      <h2 class="text-xl font-extrabold text-verde">¡Oferta recibida!</h2>
      <p class="text-gris-oscuro mt-2">Nuestro equipo revisará tu publicación y la haremos visible en la Bolsa de Trabajo. Recibirás un aviso por email cuando esté online.</p>
      <div class="mt-4"><a href="<?= e(u('/bolsa')) ?>" class="bg-azul text-white px-4 py-2 rounded font-semibold">Ver bolsa</a></div>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mt-6 text-sm">
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" novalidate class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div>
        <label class="block text-sm font-semibold mb-1">Email de la empresa registrada <span class="text-coral">*</span></label>
        <input name="company_email" type="email" required value="<?= e($old['company_email']) ?>" class="w-full border <?= isset($errors['company_email'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
        <p class="text-xs text-gris-oscuro mt-1">Usamos este email para identificar la empresa. ¿Aún no la registraste? <a href="<?= e(u('/registro-empresa')) ?>" class="text-azul hover:underline">Registra tu empresa</a> primero.</p>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Título de la oferta <span class="text-coral">*</span></label>
        <input name="title" required value="<?= e($old['title']) ?>" placeholder="Ej: Técnico de Prevención de Riesgos Laborales" class="w-full border <?= isset($errors['title'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Descripción <span class="text-coral">*</span></label>
        <textarea name="description" rows="6" required class="w-full border <?= isset($errors['description'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2"><?= e($old['description']) ?></textarea>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Categoría</label>
          <input name="category" value="<?= e($old['category']) ?>" placeholder="Seguridad, Calidad, ESG…" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Modalidad <span class="text-coral">*</span></label>
          <select name="modality" required class="w-full border <?= isset($errors['modality'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2 bg-white">
            <option value="">— Selecciona —</option>
            <?php foreach (['presencial','remoto','hibrido'] as $m): ?>
              <option value="<?= $m ?>" <?= $old['modality']===$m?'selected':'' ?>><?= e(ucfirst($m)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">País</label>
          <select name="country_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <?php foreach ($countries as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$selected_country === (int)$c['id'] ? 'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Salario mínimo (Gs.)</label>
          <input name="salary_min" type="number" value="<?= e($old['salary_min']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Salario máximo (Gs.)</label>
          <input name="salary_max" type="number" value="<?= e($old['salary_max']) ?>" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
      </div>

      <button type="submit" class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition">Enviar para revisión</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
