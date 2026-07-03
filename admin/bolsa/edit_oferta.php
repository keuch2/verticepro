<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$o = $id ? DB::one('SELECT * FROM job_offers WHERE id=?',[$id]) : null;
$is_new = !$o;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $data = [
        'company_id' => post_int('company_id'),
        'slug' => slugify(post('slug') ?: post('title')),
        'title' => trim(post('title','')),
        'description' => trim(post('description','')) ?: null,
        'category' => trim(post('category','')) ?: null,
        'modality' => post('modality') ?: null,
        'country_id' => post_int('country_id'),
        'city_id' => post_int('city_id'),
        'salary_min' => post_int('salary_min'),
        'salary_max' => post_int('salary_max'),
        'published_at' => post('published_at') ?: null,
        'status' => post('status','draft'),
    ];
    if (!$data['title'] || !$data['company_id']) { flash('err','Título y empresa requeridos'); redirect('/admin/bolsa/edit_oferta.php'.($id?"?id=$id":'')); }

    if (!empty($_FILES['flyer']['name'])) {
        $rel = upload_image($_FILES['flyer'], 'flyers', $data['slug']);
        if ($rel) $data['flyer_image'] = $rel;
    }

    if ($is_new) { $id = DB::insert('job_offers', $data); flash('ok','Oferta creada'); }
    else         { DB::update('job_offers', $data, ['id'=>$id]); flash('ok','Oferta actualizada'); }
    redirect('/admin/bolsa/edit_oferta.php?id='.$id);
}
if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('job_offers',['id'=>$id]); flash('ok','Eliminada'); redirect('/admin/bolsa/'); }

$page_title = $is_new?'Nueva oferta':'Editar oferta';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/bolsa/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Título</label><input name="title" required value="<?= e($o['title']??'') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Empresa</label><select name="company_id" required><option value="">—</option><?= opts(DB::all('SELECT id,name FROM companies ORDER BY name'),'id','name',$o['company_id']??null) ?></select></div>
      <div><label>Slug</label><input name="slug" value="<?= e($o['slug']??'') ?>" /></div>
    </div>
    <div><label>Descripción</label><textarea name="description" rows="6"><?= e($o['description']??'') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Categoría</label><input name="category" value="<?= e($o['category']??'') ?>" /></div>
      <div><label>Modalidad</label><select name="modality"><option value="">—</option><?php foreach (['presencial','remoto','hibrido'] as $m): ?><option value="<?= $m ?>" <?= ($o['modality']??'')===$m?'selected':'' ?>><?= $m ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>País</label><select id="oferta-country" name="country_id"><option value="">—</option><?= opts(SectionRepo::countries(),'id','name',$o['country_id']??null) ?></select></div>
      <div><label>Localidad</label><select id="oferta-city" name="city_id"><option value="">—</option><?php foreach (SectionRepo::cities() as $ci): ?><option value="<?= (int)$ci['id'] ?>" data-country="<?= (int)$ci['country_id'] ?>" <?= (string)($o['city_id']??'') === (string)$ci['id'] ? 'selected':'' ?>><?= e($ci['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Estado</label><select name="status"><?php foreach (['draft','published','closed'] as $s): ?><option value="<?= $s ?>" <?= ($o['status']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
      <div></div>
    </div>
    <script>
      (function() {
        const country = document.getElementById('oferta-country');
        const city    = document.getElementById('oferta-city');
        if (!country || !city) return;
        function sync() {
          const c = country.value;
          Array.from(city.options).forEach(o => {
            if (!o.value) return;
            const match = o.dataset.country === c;
            o.hidden = !match;
            if (o.hidden && o.selected) city.value = '';
          });
        }
        country.addEventListener('change', sync);
        sync();
      })();
    </script>
    <div class="form-grid cols-2">
      <div><label>Salario min (Gs.)</label><input type="number" name="salary_min" min="0" value="<?= e($o['salary_min']??'') ?>" /></div>
      <div><label>Salario max (Gs.)</label><input type="number" name="salary_max" min="0" value="<?= e($o['salary_max']??'') ?>" /></div>
    </div>
    <div><label>Fecha publicación</label><input type="datetime-local" name="published_at" value="<?= $o?date('Y-m-d\TH:i',strtotime($o['published_at']??'now')):'' ?>" /></div>
    <div><label>Flyer / imagen</label>
      <?php if (!empty($o['flyer_image'])): ?><div><img src="<?= e(img_url($o['flyer_image'])) ?>" style="max-height:160px;border:1px solid #ddd;" /></div><?php endif; ?>
      <input type="file" name="flyer" accept="image/*" />
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
