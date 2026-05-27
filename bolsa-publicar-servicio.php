<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/image.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$old = [
    'professional_email' => '', 'title' => '', 'description' => '',
    'category' => '', 'modality' => '', 'country_id' => '',
];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if ($old['professional_email'] === '' || !filter_var($old['professional_email'], FILTER_VALIDATE_EMAIL)) $errors['professional_email'] = 'Indica el email registrado del profesional.';
    if ($old['title'] === '')                                  $errors['title'] = 'Indica un título.';
    if ($old['description'] === '')                            $errors['description'] = 'Describe el servicio.';
    if ($old['modality'] === '')                               $errors['modality'] = 'Selecciona modalidad.';

    $prof = null;
    if (!isset($errors['professional_email'])) {
        $prof = DB::one('SELECT id, name, status FROM professionals WHERE email = ? LIMIT 1', [$old['professional_email']]);
        if (!$prof) {
            $errors['professional_email'] = 'No encontramos un profesional registrado con ese email. Regístrate primero.';
        } elseif ($prof['status'] !== 'active') {
            $errors['professional_email'] = 'Tu perfil todavía no está aprobado.';
        }
    }

    if (!$errors) {
        $base = slugify($old['title']) ?: 'servicio';
        $slug = $base; $i = 2;
        while (DB::one('SELECT id FROM services WHERE slug = ? LIMIT 1', [$slug])) {
            $slug = $base . '-' . $i++;
            if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
        }
        $flyer = null;
        if (!empty($_FILES['flyer']['name'])) {
            $rel = upload_image($_FILES['flyer'], 'flyers', $slug);
            if ($rel) $flyer = $rel;
        }
        DB::insert('services', [
            'professional_id' => (int)$prof['id'],
            'slug'            => $slug,
            'title'           => $old['title'],
            'description'     => $old['description'],
            'flyer_image'     => $flyer,
            'category'        => $old['category'] ?: null,
            'modality'        => $old['modality'] ?: null,
            'country_id'      => $old['country_id'] !== '' ? (int)$old['country_id'] : null,
            'status'          => 'draft',
        ]);
        $submitted_ok = true;
        $old = ['professional_email'=>'','title'=>'','description'=>'','category'=>'','modality'=>'','country_id'=>''];
    }
}

$countries = SectionRepo::countries();
$default_country_id = (int)(DB::one("SELECT id FROM countries WHERE slug = 'paraguay'")['id'] ?? 0);
$selected_country = $old['country_id'] !== '' ? (int)$old['country_id'] : $default_country_id;

$page_title = 'Publicar servicio profesional — Vértice Pro';
$page_active = 'bolsa-publicar-servicio.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-6 py-14">
  <h1 class="text-3xl font-extrabold">Publicar servicio profesional</h1>
  <p class="text-gris-oscuro mt-2">Si eres profesional registrado, puedes publicar tus servicios. La publicación es gratuita y queda en revisión.</p>

  <?php if ($submitted_ok): ?>
    <div class="bg-verde/10 border border-verde rounded-lg p-6 mt-6">
      <h2 class="text-xl font-extrabold text-verde">¡Servicio recibido!</h2>
      <p class="text-gris-oscuro mt-2">Lo revisamos y lo haremos visible pronto. Te avisaremos por email.</p>
      <div class="mt-4"><a href="<?= e(u('/bolsa')) ?>" class="bg-azul text-white px-4 py-2 rounded font-semibold">Ver bolsa</a></div>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mt-6 text-sm">
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" novalidate class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />

      <div>
        <label class="block text-sm font-semibold mb-1">Tu email profesional <span class="text-coral">*</span></label>
        <input name="professional_email" type="email" required value="<?= e($old['professional_email']) ?>" class="w-full border <?= isset($errors['professional_email'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
        <p class="text-xs text-gris-oscuro mt-1">¿Aún no tienes perfil? <a href="<?= e(u('/registro')) ?>" class="text-azul hover:underline">Únete a la red</a> primero.</p>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Título del servicio <span class="text-coral">*</span></label>
        <input name="title" required value="<?= e($old['title']) ?>" placeholder="Ej: Auditoría ISO 45001" class="w-full border <?= isset($errors['title'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
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

      <div>
        <label class="block text-sm font-semibold mb-1">Flyer / imagen del servicio (opcional)</label>
        <input name="flyer" type="file" accept="image/*" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
        <p class="text-xs text-gris-oscuro mt-1">Se mostrará junto a tu servicio en la Bolsa. Recomendado: JPG/PNG/WebP, máx 8 MB.</p>
      </div>

      <button type="submit" class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition">Enviar para revisión</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
