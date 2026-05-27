<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ev = $id ? DB::one('SELECT * FROM events WHERE id=?', [$id]) : null;
$is_new = !$ev;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'slug'          => slugify(post('slug') ?: post('title')),
        'title'         => trim(post('title','')),
        'description'   => trim(post('description','')) ?: null,
        'starts_at'     => post('starts_at') ?: null,
        'ends_at'       => post('ends_at') ?: null,
        'modality'      => post('modality') ?: null,
        'location'      => trim(post('location','')) ?: null,
        'url'           => trim(post('url','')) ?: null,
        'discipline_id' => post_int('discipline_id'),
        'status'        => post('status','draft'),
    ];
    if (!$data['title'])     { flash('err','Título requerido'); redirect('/admin/eventos/edit.php' . ($id ? "?id=$id" : '')); }
    if (!$data['starts_at']) { flash('err','Fecha requerida'); redirect('/admin/eventos/edit.php' . ($id ? "?id=$id" : '')); }

    if (!empty($_FILES['cover']['name'])) {
        $rel = upload_image($_FILES['cover'], 'events', $data['slug']);
        if ($rel) $data['cover_image'] = $rel;
    }

    if ($is_new) { $id = DB::insert('events', $data); flash('ok','Evento creado'); }
    else         { DB::update('events', $data, ['id'=>$id]); flash('ok','Evento actualizado'); }
    redirect('/admin/eventos/edit.php?id=' . $id);
}

if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('events', ['id'=>$id]); flash('ok','Eliminado'); redirect('/admin/eventos/'); }

$page_title = $is_new ? 'Nuevo evento' : 'Editar evento';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/eventos/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div class="form-grid cols-2">
      <div><label>Título</label><input name="title" required value="<?= e($ev['title']??'') ?>" /></div>
      <div><label>Slug</label><input name="slug" value="<?= e($ev['slug']??'') ?>" placeholder="auto" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Comienza</label><input type="datetime-local" name="starts_at" required value="<?= $ev?date('Y-m-d\TH:i',strtotime($ev['starts_at']??'now')):'' ?>" /></div>
      <div><label>Termina</label><input type="datetime-local" name="ends_at" value="<?= !empty($ev['ends_at'])?date('Y-m-d\TH:i',strtotime($ev['ends_at'])):'' ?>" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Modalidad</label>
        <select name="modality">
          <option value="">—</option>
          <?php foreach (['presencial','virtual','hibrido'] as $m): ?><option value="<?= $m ?>" <?= ($ev['modality']??'')===$m?'selected':'' ?>><?= e(ucfirst($m)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label>Ubicación</label><input name="location" value="<?= e($ev['location']??'') ?>" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>URL externa</label><input name="url" value="<?= e($ev['url']??'') ?>" placeholder="https://…" /></div>
      <div><label>Disciplina</label><select name="discipline_id"><option value="">—</option><?= opts(SectionRepo::disciplines(),'id','name',$ev['discipline_id']??null) ?></select></div>
    </div>
    <div><label>Descripción</label><textarea name="description" rows="5"><?= e($ev['description']??'') ?></textarea></div>
    <div><label>Portada</label>
      <?php if (!empty($ev['cover_image'])): ?><div><img src="<?= e(img_url($ev['cover_image'])) ?>" style="max-height:120px;" /></div><?php endif; ?>
      <input type="file" name="cover" accept="image/*" />
    </div>
    <div><label>Estado</label>
      <select name="status">
        <?php foreach (['draft','published'] as $s): ?><option value="<?= $s ?>" <?= ($ev['status']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
