<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$c = $id ? CompanyRepo::find($id) : null;
$is_new = !$c;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'slug' => slugify(post('slug') ?: post('name')),
        'name' => trim(post('name','')),
        'description' => trim(post('description','')) ?: null,
        'sector_id' => post_int('sector_id'),
        'country_id' => post_int('country_id'),
        'city_id' => post_int('city_id') ?: null,
        'size' => post('size') ?: null,
        'founded_year' => post_int('founded_year'),
        'website' => trim(post('website','')) ?: null,
        'email' => trim(post('email','')) ?: null,
        'verified' => post_bool('verified'),
        'status' => post('status','active'),
    ];
    if (!$data['name']) { flash('err','Nombre requerido'); redirect('/admin/empresas/edit.php'.($id?"?id=$id":'')); }

    if (!empty($_FILES['logo']['name'])) {
        $rel = upload_image($_FILES['logo'], 'companies', $data['slug']);
        if ($rel) $data['logo_image'] = $rel;
    }

    $prev_status = $c['status'] ?? null;
    if ($is_new) { $id = DB::insert('companies', $data); flash('ok','Organización creada'); }
    else         { DB::update('companies', $data, ['id'=>$id]); flash('ok','Organización actualizada'); }

    // Aprobación: activar también el users vinculado + notificar a la empresa
    if (!$is_new && $prev_status === 'pending' && $data['status'] === 'active') {
        $fresh = CompanyRepo::find($id);
        if ($fresh && !empty($fresh['email'])) {
            if (!empty($fresh['user_id'])) {
                DB::update('users', ['status' => 'active'], ['id' => (int)$fresh['user_id']]);
            }
            $link = u('/mi-organizacion');
            $title = '¡Tu organización fue aprobada en Vértice Pro!';
            $body  = "Buenas noticias. La organización " . $fresh['name'] . " acaba de ser aprobada y ya es visible en el directorio de organizaciones. Ya puedes iniciar sesión con tu email y contraseña para editar el perfil de la organización y publicar ofertas.";
            if (!empty($fresh['user_id'])) {
                Notify::create((int)$fresh['user_id'], 'profile_approved', $title, $body, $link, $fresh['email']);
            } else {
                Notify::emailOnly($fresh['email'], $fresh['name'], (int)($fresh['notifications_opt_in'] ?? 1), $title, $body, $link);
            }
        }
    }
    redirect('/admin/empresas/edit.php?id='.$id);
}

if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('companies',['id'=>$id]); flash('ok','Eliminada'); redirect('/admin/empresas/'); }

$page_title = $is_new?'Nueva organización':'Editar organización';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/empresas/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div class="form-grid cols-2">
      <div><label>Nombre</label><input name="name" required value="<?= e($c['name']??'') ?>" /></div>
      <div><label>Slug</label><input name="slug" value="<?= e($c['slug']??'') ?>" /></div>
    </div>
    <div><label>Descripción</label><textarea name="description" rows="4"><?= e($c['description']??'') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Sector</label><select name="sector_id"><option value="">—</option><?= opts(SectionRepo::sectors(),'id','name',$c['sector_id']??null) ?></select></div>
      <div><label>País</label><select name="country_id"><option value="">—</option><?= opts(SectionRepo::countries(),'id','name',$c['country_id']??null) ?></select></div>
    </div>
    <div><label>Ciudad</label><select name="city_id"><option value="">—</option><?= opts(SectionRepo::cities(),'id','name',$c['city_id']??null) ?></select></div>
    <div class="form-grid cols-2">
      <div>
        <label>Tamaño</label>
        <select name="size">
          <option value="">—</option>
          <?php foreach (['1-10','11-50','51-200','200+'] as $s): ?><option value="<?= $s ?>" <?= ($c['size']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label>Año de fundación</label><input type="number" name="founded_year" value="<?= e($c['founded_year']??'') ?>" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Web</label><input name="website" value="<?= e($c['website']??'') ?>" /></div>
      <div><label>Email</label><input type="email" name="email" value="<?= e($c['email']??'') ?>" /></div>
    </div>
    <div><label>Logo</label>
      <?php if (!empty($c['logo_image'])): ?><div><img src="<?= e(img_url($c['logo_image'])) ?>" style="max-height:60px;" /></div><?php endif; ?>
      <input type="file" name="logo" accept="image/*" />
    </div>
    <div style="display:flex;gap:20px;">
      <label><input type="checkbox" name="verified" value="1" <?= !empty($c['verified'])?'checked':'' ?> style="width:auto;" /> Verificada</label>
    </div>
    <div><label>Estado</label>
      <select name="status">
        <?php foreach (['active','pending','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($c['status']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
