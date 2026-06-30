<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$r = $id ? DB::one('SELECT * FROM resources WHERE id=?',[$id]) : null;
$is_new = !$r;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $data = [
        'slug' => slugify(post('slug') ?: post('title')),
        'title' => trim(post('title','')),
        'description' => trim(post('description','')) ?: null,
        'category' => trim(post('category','')) ?: null,
        'status' => post('status','published'),
    ];
    if (!$data['title']) { flash('err','Título requerido'); redirect('/admin/recursos/edit.php'.($id?"?id=$id":'')); }

    $has_upload = !empty($_FILES['file']['name']) && ($_FILES['file']['error'] ?? 1) === UPLOAD_ERR_OK;
    if ($has_upload) {
        $dir = cfg()['img_path'] . '/../uploads/resources';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = $data['slug'] . '-' . time() . '.' . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $filename)) {
            $data['file_path'] = 'uploads/resources/' . $filename;
        }
    }

    // Un recurso descargable necesita un archivo: lo exigimos al crear, o al editar
    // si todavía no había ninguno cargado.
    if (empty($data['file_path']) && empty($r['file_path'])) {
        flash_old(); flash('err','Debes adjuntar un archivo para el recurso');
        redirect('/admin/recursos/edit.php'.($id?"?id=$id":''));
    }

    if ($is_new) { $id = DB::insert('resources', $data); flash('ok','Recurso creado'); }
    else         { DB::update('resources', $data, ['id'=>$id]); flash('ok','Recurso actualizado'); }
    redirect('/admin/recursos/edit.php?id='.$id);
}
if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('resources',['id'=>$id]); flash('ok','Eliminado'); redirect('/admin/recursos/'); }

$page_title = $is_new?'Nuevo recurso':'Editar recurso';
// Tras un error de validación, repoblar con lo que el usuario escribió.
$f = has_old() ? $_SESSION['old'] : ($r ?? []);
old_clear();
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/recursos/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Título</label><input name="title" required value="<?= e($f['title'] ?? '') ?>" /></div>
    <div><label>Categoría</label><input name="category" value="<?= e($f['category'] ?? '') ?>" /></div>
    <div><label>Descripción</label><textarea name="description" rows="4"><?= e($f['description'] ?? '') ?></textarea></div>
    <div><label>Archivo</label>
      <?php if (!empty($r['file_path'])): ?><div><a href="/<?= e($r['file_path']) ?>" target="_blank">Archivo actual</a></div><?php endif; ?>
      <input type="file" name="file" />
    </div>
    <div><label>Estado</label>
      <?php $cur_status = $f['status'] ?? 'published'; ?>
      <select name="status"><?php foreach (['draft','published'] as $s): ?><option value="<?= $s ?>" <?= $cur_status===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
