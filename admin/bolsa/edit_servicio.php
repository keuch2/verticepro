<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$s = $id ? DB::one('SELECT * FROM services WHERE id=?',[$id]) : null;
$is_new = !$s;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $data = [
        'professional_id' => post_int('professional_id'),
        'slug' => slugify(post('slug') ?: post('title')),
        'title' => trim(post('title','')),
        'description' => trim(post('description','')) ?: null,
        'category' => trim(post('category','')) ?: null,
        'modality' => post('modality') ?: null,
        'country_id' => post_int('country_id'),
        'price_range' => trim(post('price_range','')) ?: null,
        'published_at' => post('published_at') ?: null,
        'status' => post('status','draft'),
    ];
    if (!$data['title'] || !$data['professional_id']) { flash('err','Título y profesional requeridos'); redirect('/admin/bolsa/edit_servicio.php'.($id?"?id=$id":'')); }

    if (!empty($_FILES['flyer']['name'])) {
        $rel = upload_image($_FILES['flyer'], 'flyers', $data['slug']);
        if ($rel) $data['flyer_image'] = $rel;
    }

    if ($is_new) { $id = DB::insert('services', $data); flash('ok','Servicio creado'); }
    else         { DB::update('services', $data, ['id'=>$id]); flash('ok','Servicio actualizado'); }
    redirect('/admin/bolsa/edit_servicio.php?id='.$id);
}
if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('services',['id'=>$id]); flash('ok','Eliminado'); redirect('/admin/bolsa/?tab=servicios'); }

$page_title = $is_new?'Nuevo servicio':'Editar servicio';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/bolsa/?tab=servicios')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Título</label><input name="title" required value="<?= e($s['title']??'') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Profesional</label><select name="professional_id" required><option value="">—</option><?= opts(DB::all('SELECT id,name FROM professionals ORDER BY name'),'id','name',$s['professional_id']??null) ?></select></div>
      <div><label>Slug</label><input name="slug" value="<?= e($s['slug']??'') ?>" /></div>
    </div>
    <div><label>Descripción</label><textarea name="description" rows="6"><?= e($s['description']??'') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Categoría</label><input name="category" value="<?= e($s['category']??'') ?>" /></div>
      <div><label>Modalidad</label><select name="modality"><option value="">—</option><?php foreach (['presencial','remoto','hibrido'] as $m): ?><option value="<?= $m ?>" <?= ($s['modality']??'')===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>País</label><select name="country_id"><option value="">—</option><?= opts(SectionRepo::countries(),'id','name',$s['country_id']??null) ?></select></div>
      <div><label>Rango de precio</label><input name="price_range" value="<?= e($s['price_range']??'') ?>" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Estado</label><select name="status"><?php foreach (['draft','published','closed'] as $st): ?><option value="<?= $st ?>" <?= ($s['status']??'')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
      <div><label>Publicado</label><input type="datetime-local" name="published_at" value="<?= $s?date('Y-m-d\TH:i',strtotime($s['published_at']??'now')):'' ?>" /></div>
    </div>
    <div><label>Flyer / imagen</label>
      <?php if (!empty($s['flyer_image'])): ?><div><img src="<?= e(img_url($s['flyer_image'])) ?>" style="max-height:160px;border:1px solid #ddd;" /></div><?php endif; ?>
      <input type="file" name="flyer" accept="image/*" />
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
