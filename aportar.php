<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/upload.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$old = ['name' => '', 'email' => '', 'title' => '', 'description' => '', 'category' => '', 'discipline_id' => ''];
$submitted_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if ($old['name'] === '')                                          $errors['name'] = 'Indica tu nombre.';
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email inválido.';
    if ($old['title'] === '')                                         $errors['title'] = 'Indica un título para el aporte.';
    if (empty($_FILES['file']['name']))                               $errors['file']  = 'Adjunta un archivo.';

    if (!$errors) {
        $up = upload_doc($_FILES['file'], 'contributions', $old['title']);
        if (!$up) {
            $errors['file'] = 'No pudimos procesar el archivo (verifica formato y tamaño máx. 15MB).';
        } else {
            DB::insert('user_contributions', [
                'user_id'       => null,
                'guest_name'    => $old['name'],
                'guest_email'   => $old['email'],
                'title'         => $old['title'],
                'description'   => $old['description'] ?: null,
                'file_path'     => $up['path'],
                'file_size'     => $up['size'],
                'file_mime'     => $up['mime'],
                'category'      => $old['category'] ?: null,
                'discipline_id' => $old['discipline_id'] !== '' ? (int)$old['discipline_id'] : null,
                'status'        => 'pending',
            ]);
            $submitted_ok = true;
            $old = ['name'=>'','email'=>'','title'=>'','description'=>'','category'=>'','discipline_id'=>''];
        }
    }
}

$disciplines = SectionRepo::disciplines();

$page_title = 'Aportar contenido — Vértice Pro';
$page_active = 'aportar.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-3xl mx-auto px-6 py-14">
  <h1 class="text-3xl font-extrabold">Aportar contenido</h1>
  <p class="text-gris-oscuro mt-2">Comparte formularios, guías, plantillas u otros recursos útiles para la comunidad. Todos los aportes son revisados por nuestro equipo antes de ser publicados.</p>

  <?php if ($submitted_ok): ?>
    <div class="bg-verde/10 border border-verde rounded-lg p-6 mt-6">
      <h2 class="text-xl font-extrabold text-verde">¡Aporte recibido!</h2>
      <p class="text-gris-oscuro mt-2">Gracias. Tu archivo quedó en revisión y te avisaremos por email cuando lo publiquemos en la biblioteca.</p>
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded-lg p-4 mt-6 text-sm">
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">Tu nombre <span class="text-coral">*</span></label>
          <input name="name" required value="<?= e($old['name']) ?>" class="w-full border <?= isset($errors['name'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Email <span class="text-coral">*</span></label>
          <input name="email" type="email" required value="<?= e($old['email']) ?>" class="w-full border <?= isset($errors['email'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Título del aporte <span class="text-coral">*</span></label>
        <input name="title" required value="<?= e($old['title']) ?>" placeholder="Ej: Formulario de inspección de altura" class="w-full border <?= isset($errors['title'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-1">Categoría</label>
          <input name="category" value="<?= e($old['category']) ?>" placeholder="Plantilla, Guía, Checklist…" class="w-full border border-gray-300 rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Disciplina</label>
          <select name="discipline_id" class="w-full border border-gray-300 rounded px-3 py-2 bg-white">
            <option value="">— Selecciona —</option>
            <?php foreach ($disciplines as $d): ?>
              <option value="<?= (int)$d['id'] ?>" <?= (string)$old['discipline_id'] === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Descripción</label>
        <textarea name="description" rows="4" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Cuéntanos brevemente para qué sirve el archivo."><?= e($old['description']) ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Archivo <span class="text-coral">*</span></label>
        <input name="file" type="file" required class="w-full border <?= isset($errors['file'])?'border-coral':'border-gray-300' ?> rounded px-3 py-2" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png" />
        <p class="text-xs text-gris-oscuro mt-1">Formatos: PDF, DOC, XLS, CSV, JPG, PNG. Tamaño máximo: 15 MB.</p>
      </div>
      <div class="bg-gris-claro rounded p-4 text-sm text-gris-oscuro">
        Tu aporte quedará pendiente de revisión por nuestro equipo. Nos reservamos el derecho de no publicar contenidos que no cumplan con la <a href="<?= e(u('/terminos')) ?>" class="text-azul hover:underline">política editorial</a>.
      </div>
      <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition" type="submit">Enviar aporte</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
