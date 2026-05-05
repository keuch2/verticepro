<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = $id ? DB::one('SELECT * FROM publications WHERE id=?',[$id]) : null;
$is_new = !$p;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $data = [
        'slug' => slugify(post('slug') ?: post('title')),
        'title' => trim(post('title','')),
        'author_name' => trim(post('author_name','')) ?: null,
        'publication_type_id' => post_int('publication_type_id'),
        'discipline_id' => post_int('discipline_id'),
        'description' => trim(post('description','')) ?: null,
        'published_at' => post('published_at') ?: null,
        'status' => post('status','draft'),
    ];
    if (!$data['title']) { flash('err','Título requerido'); redirect('/admin/publicaciones/edit.php'.($id?"?id=$id":'')); }

    if (!empty($_FILES['cover']['name'])) {
        $rel = upload_image($_FILES['cover'], 'publications', $data['slug']);
        if ($rel) $data['cover_image'] = $rel;
    }
    if (!empty($_FILES['file']['name']) && ($_FILES['file']['error'] ?? 1) === UPLOAD_ERR_OK) {
        $dir = cfg()['img_path'] . '/../uploads/publications';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = $data['slug'] . '-' . time() . '.' . pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $filename)) {
            $data['file_path'] = 'uploads/publications/' . $filename;
        }
    }

    if ($is_new) { $id = DB::insert('publications', $data); flash('ok','Creada'); }
    else         { DB::update('publications', $data, ['id'=>$id]); flash('ok','Actualizada'); }
    redirect('/admin/publicaciones/edit.php?id='.$id);
}
if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('publications',['id'=>$id]); flash('ok','Eliminada'); redirect('/admin/publicaciones/'); }

$page_title = $is_new?'Nueva publicación':'Editar publicación';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/publicaciones/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Título</label><input name="title" required value="<?= e($p['title']??'') ?>" /></div>
    <div><label>Autor</label><input name="author_name" value="<?= e($p['author_name']??'') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Tipo</label><select name="publication_type_id"><option value="">—</option><?= opts(SectionRepo::pubTypes(),'id','name',$p['publication_type_id']??null) ?></select></div>
      <div><label>Disciplina</label><select name="discipline_id"><option value="">—</option><?= opts(SectionRepo::disciplines(),'id','name',$p['discipline_id']??null) ?></select></div>
    </div>
    <div><label>Descripción</label><textarea name="description" rows="4"><?= e($p['description']??'') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Estado</label><select name="status"><?php foreach (['draft','published'] as $st): ?><option value="<?= $st ?>" <?= ($p['status']??'')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
      <div><label>Publicada</label><input type="datetime-local" name="published_at" value="<?= $p?date('Y-m-d\TH:i',strtotime($p['published_at']??'now')):'' ?>" /></div>
    </div>
    <div><label>Portada</label>
      <?php if (!empty($p['cover_image'])): ?><div><img src="<?= e(img_url($p['cover_image'])) ?>" style="max-height:120px;" /></div><?php endif; ?>
      <input type="file" name="cover" accept="image/*" />
    </div>
    <div><label>Archivo (PDF)</label>
      <?php if (!empty($p['file_path'])): ?><div><a href="/<?= e($p['file_path']) ?>" target="_blank">Archivo actual</a></div><?php endif; ?>
      <input type="file" name="file" accept="application/pdf" />
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
