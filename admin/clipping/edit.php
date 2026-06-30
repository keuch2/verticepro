<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$n = $id ? DB::one('SELECT * FROM news_clippings WHERE id=?', [$id]) : null;
$is_new = !$n;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'title'         => trim(post('title','')),
        'source_name'   => trim(post('source_name','')),
        'source_url'    => trim(post('source_url','')),
        'summary'       => trim(post('summary','')) ?: null,
        'published_at'  => post('published_at') ?: date('Y-m-d'),
        'discipline_id' => post_int('discipline_id'),
        'section_slug'  => post('section_slug') ?: null,
        'status'        => post('status','published'),
    ];
    if (!$data['title'])       { flash_old(); flash('err','Título requerido'); redirect('/admin/clipping/edit.php' . ($id ? "?id=$id" : '')); }
    if (!$data['source_name']) { flash_old(); flash('err','Fuente requerida'); redirect('/admin/clipping/edit.php' . ($id ? "?id=$id" : '')); }
    if (!preg_match('#^https?://#i', $data['source_url'])) { flash_old(); flash('err','URL inválida'); redirect('/admin/clipping/edit.php' . ($id ? "?id=$id" : '')); }

    if ($is_new) { $id = DB::insert('news_clippings', $data); flash('ok','Noticia creada'); }
    else         { DB::update('news_clippings', $data, ['id'=>$id]); flash('ok','Noticia actualizada'); }
    redirect('/admin/clipping/edit.php?id=' . $id);
}

if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('news_clippings', ['id'=>$id]); flash('ok','Eliminada'); redirect('/admin/clipping/'); }

$page_title = $is_new ? 'Nueva noticia' : 'Editar noticia';
// Si venimos de un error de validación, repoblamos con lo que el usuario escribió
// (old input); si no, con la fila existente. Así no se pierden los datos cargados.
$f = has_old() ? ($_SESSION['old']) : ($n ?? []);
old_clear();
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/clipping/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Título</label><input name="title" required value="<?= e($f['title'] ?? '') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Fuente</label><input name="source_name" required value="<?= e($f['source_name'] ?? '') ?>" placeholder="Ej: ABC Color" /></div>
      <div><label>URL de la noticia</label><input name="source_url" required value="<?= e($f['source_url'] ?? '') ?>" placeholder="https://…" /></div>
    </div>
    <div><label>Resumen</label><textarea name="summary" rows="4"><?= e($f['summary'] ?? '') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Fecha</label><input type="date" name="published_at" value="<?= e($f['published_at'] ?? date('Y-m-d')) ?>" /></div>
      <div><label>Disciplina</label><select name="discipline_id"><option value="">—</option><?= opts(SectionRepo::disciplines(),'id','name',$f['discipline_id'] ?? null) ?></select></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Sección editorial</label><select name="section_slug"><option value="">—</option><?= opts(SectionRepo::all(),'slug','name',$f['section_slug'] ?? null) ?></select></div>
      <div><label>Estado</label>
        <?php $cur_status = $f['status'] ?? 'published'; ?>
        <select name="status">
          <?php foreach (['published','draft'] as $s): ?><option value="<?= $s ?>" <?= $cur_status===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
