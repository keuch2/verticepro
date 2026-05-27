<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = $id ? ProfessionalRepo::find($id) : null;
$is_new = !$p;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'slug' => slugify(post('slug') ?: post('name')),
        'name' => trim(post('name','')),
        'title' => trim(post('title','')),
        'bio' => trim(post('bio','')) ?: null,
        'city_id' => post_int('city_id'),
        // type_id se sincroniza más abajo desde el multi-select 'types[]'
        'type_id' => (!empty($_POST['types']) && is_array($_POST['types'])) ? (int)$_POST['types'][0] : null,
        'email' => trim(post('email','')) ?: null,
        'linkedin' => trim(post('linkedin','')) ?: null,
        'website' => trim(post('website','')) ?: null,
        'verified' => post_bool('verified'),
        'available' => post_bool('available'),
        'featured' => post_bool('featured'),
        'stats_years_exp' => post_int('stats_years_exp') ?? 0,
        'stats_articles' => post_int('stats_articles') ?? 0,
        'stats_connections' => post_int('stats_connections') ?? 0,
        'stats_projects' => post_int('stats_projects') ?? 0,
        'status' => post('status','active'),
    ];
    if (!$data['name']) { flash('err','Nombre requerido'); redirect('/admin/profesionales/edit.php'.($id?"?id=$id":'')); }

    if (!empty($_FILES['avatar']['name'])) {
        $rel = upload_image($_FILES['avatar'], 'profiles', $data['slug']);
        if ($rel) $data['avatar_image'] = $rel;
    }

    $prev_status = $p['status'] ?? null;
    if ($is_new) { $id = DB::insert('professionals', $data); flash('ok','Profesional creado'); }
    else         { DB::update('professionals', $data, ['id'=>$id]); flash('ok','Profesional actualizado'); }

    // Aprobación: activar también el users vinculado + notificar al profesional
    if (!$is_new && $prev_status === 'pending' && $data['status'] === 'active') {
        $fresh = ProfessionalRepo::find($id);
        if ($fresh) {
            if (!empty($fresh['user_id'])) {
                DB::update('users', ['status' => 'active'], ['id' => (int)$fresh['user_id']]);
            }
            $link = u('/mi-perfil');
            $title = '¡Tu perfil profesional fue aprobado!';
            $body  = "Buenas noticias, " . $fresh['name'] . ". Tu perfil en la Red Vértice Pro acaba de ser aprobado y ya es visible en el directorio. Ya puedes iniciar sesión con tu email y la contraseña que elegiste al registrarte para editar tu perfil.";
            if (!empty($fresh['user_id'])) {
                Notify::create((int)$fresh['user_id'], 'profile_approved', $title, $body, $link, $fresh['email']);
            } elseif (!empty($fresh['email'])) {
                Notify::emailOnly($fresh['email'], $fresh['name'], (int)($fresh['notifications_opt_in'] ?? 1), $title, $body, $link);
            }
        }
    }

    // Tipos (M:N)
    ProfessionalRepo::setTypes($id, (array)($_POST['types'] ?? []));

    // Disciplinas
    DB::run('DELETE FROM professional_disciplines WHERE professional_id = ?', [$id]);
    foreach ((array)($_POST['disciplines'] ?? []) as $did) {
        DB::insert('professional_disciplines', ['professional_id'=>$id, 'discipline_id'=>(int)$did]);
    }
    // Especialidades (chip input csv)
    DB::run('DELETE FROM professional_specialties WHERE professional_id = ?', [$id]);
    foreach (array_filter(array_map('trim', explode(',', post('specialties','')))) as $s) {
        try { DB::insert('professional_specialties', ['professional_id'=>$id, 'specialty'=>$s]); } catch (\Throwable $e) {}
    }
    redirect('/admin/profesionales/edit.php?id='.$id);
}

if (isset($_GET['delete']) && $id) { csrf_check(); DB::delete('professionals', ['id'=>$id]); flash('ok','Eliminado'); redirect('/admin/profesionales/'); }

$current_disc  = $id ? array_column(DB::all('SELECT discipline_id FROM professional_disciplines WHERE professional_id=?',[$id]),'discipline_id') : [];
$current_types = $id ? ProfessionalRepo::typeIds($id) : [];
$specs_csv = $id ? implode(', ', ProfessionalRepo::specialties($id)) : '';
$page_title = $is_new?'Nuevo profesional':'Editar profesional';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new): ?>
      <a href="<?= e(u('/perfil/' . $p['slug'])) ?>" target="_blank" class="btn secondary">Ver perfil</a>
      <a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
    <?php endif; ?>
    <a href="<?= e(u('/admin/profesionales/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div class="form-grid cols-2">
      <div><label>Nombre</label><input name="name" required value="<?= e($p['name']??'') ?>" /></div>
      <div><label>Slug</label><input name="slug" value="<?= e($p['slug']??'') ?>" placeholder="auto desde nombre" /></div>
    </div>
    <div><label>Título profesional</label><input name="title" value="<?= e($p['title']??'') ?>" /></div>
    <div><label>Bio</label><textarea name="bio" rows="5"><?= e($p['bio']??'') ?></textarea></div>
    <div class="form-grid cols-2">
      <div><label>Ciudad</label><select name="city_id"><option value="">—</option><?= opts(SectionRepo::cities(),'id','name',$p['city_id']??null) ?></select></div>
      <div><label>Tipos (multi)</label>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
          <?php foreach (SectionRepo::profTypes() as $t): ?>
            <label style="font-weight:400;"><input type="checkbox" name="types[]" value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $current_types, true) ? 'checked' : '' ?> style="width:auto;margin-right:4px;" /><?= e($t['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div>
      <label>Disciplinas (multi)</label>
      <div style="display:flex;flex-wrap:wrap;gap:10px;">
        <?php foreach (SectionRepo::disciplines() as $d): ?>
          <label style="font-weight:400;"><input type="checkbox" name="disciplines[]" value="<?= (int)$d['id'] ?>" <?= in_array($d['id'], $current_disc)?'checked':'' ?> style="width:auto;margin-right:4px;" /><?= e($d['name']) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div><label>Especialidades (coma-separadas)</label><input name="specialties" value="<?= e($specs_csv) ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Email</label><input type="email" name="email" value="<?= e($p['email']??'') ?>" /></div>
      <div><label>LinkedIn</label><input name="linkedin" value="<?= e($p['linkedin']??'') ?>" /></div>
    </div>
    <div><label>Website</label><input name="website" value="<?= e($p['website']??'') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Años experiencia</label><input type="number" name="stats_years_exp" value="<?= (int)($p['stats_years_exp']??0) ?>" /></div>
      <div><label>Artículos publicados</label><input type="number" name="stats_articles" value="<?= (int)($p['stats_articles']??0) ?>" /></div>
    </div>
    <div class="form-grid cols-2">
      <div><label>Conexiones</label><input type="number" name="stats_connections" value="<?= (int)($p['stats_connections']??0) ?>" /></div>
      <div><label>Proyectos</label><input type="number" name="stats_projects" value="<?= (int)($p['stats_projects']??0) ?>" /></div>
    </div>
    <div style="display:flex;gap:20px;">
      <label><input type="checkbox" name="verified" value="1" <?= !empty($p['verified'])?'checked':'' ?> style="width:auto;" /> Verificado</label>
      <label><input type="checkbox" name="available" value="1" <?= !empty($p['available'])?'checked':'' ?> style="width:auto;" /> Disponible</label>
      <label><input type="checkbox" name="featured" value="1" <?= !empty($p['featured'])?'checked':'' ?> style="width:auto;" /> Destacado en home</label>
    </div>
    <div>
      <label>Avatar</label>
      <?php if (!empty($p['avatar_image'])): ?><div><img src="<?= e(img_url($p['avatar_image'])) ?>" style="max-height:80px;border-radius:50%;" /></div><?php endif; ?>
      <input type="file" name="avatar" accept="image/*" />
    </div>
    <div>
      <label>Estado</label>
      <select name="status">
        <?php foreach (['active','pending','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($p['status']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar cambios' ?></button>
  </div>
</form>
<?php if (!$is_new): ?>
  <p style="color:#54636F;font-size:13px;margin-top:16px;">Formación, experiencia y servicios ofrecidos se gestionan en secciones aparte (próximamente editor en línea).</p>
<?php endif; ?>
<?php include __DIR__ . '/../_layout_end.php'; ?>
