<?php
require_once __DIR__ . '/../_helpers.php';
$me = auth_user();
if ($me['role'] !== 'admin') { http_response_code(403); exit('Solo admin'); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$u = $id ? DB::one('SELECT * FROM users WHERE id=?',[$id]) : null;
// Si se pidió un id concreto que no existe, es un 404 — no un "usuario nuevo".
if ($id && !$u) { http_response_code(404); flash('err','Usuario no encontrado'); redirect('/admin/usuarios/'); }
$is_new = !$u;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $data = [
        'email' => trim(post('email','')),
        'name' => trim(post('name','')),
        'role' => post('role','author'),
        'status' => post('status','active'),
    ];
    if (!$data['email'] || !$data['name']) { flash('err','Email y nombre requeridos'); redirect('/admin/usuarios/edit.php'.($id?"?id=$id":'')); }
    $new_pass = post('password','');
    // Longitud mínima de contraseña (cuentas con acceso al panel).
    if ($new_pass !== '' && strlen($new_pass) < 8) {
        flash('err','La contraseña debe tener al menos 8 caracteres');
        redirect('/admin/usuarios/edit.php'.($id?"?id=$id":''));
    }
    if ($new_pass) $data['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
    if ($is_new && !$new_pass) { flash('err','Contraseña requerida para nuevo usuario'); redirect('/admin/usuarios/edit.php'); }
    // El email es UNIQUE: si ya existe, MySQL lanza una excepción de duplicado.
    // La capturamos para mostrar un mensaje claro en vez de una pantalla de error.
    try {
        if ($is_new) { $id = DB::insert('users', $data); flash('ok','Usuario creado'); }
        else         { DB::update('users', $data, ['id'=>$id]); flash('ok','Usuario actualizado'); }
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') {
            flash('err','Ya existe un usuario con ese email');
        } else {
            flash('err','No se pudo guardar el usuario');
        }
        redirect('/admin/usuarios/edit.php'.($id?"?id=$id":''));
    }
    redirect('/admin/usuarios/edit.php?id='.$id);
}
if (isset($_GET['delete']) && $id) {
    csrf_check();
    if ($id === (int)$me['id']) { flash('err','No puedes eliminarte a ti mismo'); redirect('/admin/usuarios/'); }
    DB::delete('users',['id'=>$id]); flash('ok','Eliminado'); redirect('/admin/usuarios/');
}

$page_title = $is_new?'Nuevo usuario':'Editar usuario';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;"><?= e($page_title) ?></h1>
  <div>
    <?php if (!$is_new && $id !== (int)$me['id']): ?><a href="?id=<?= $id ?>&delete=1&csrf=<?= e(csrf_token()) ?>" class="btn danger" onclick="return confirm('¿Eliminar?')">Eliminar</a><?php endif; ?>
    <a href="<?= e(u('/admin/usuarios/')) ?>" class="btn secondary">Volver</a>
  </div>
</div>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Nombre</label><input name="name" required value="<?= e($u['name']??'') ?>" /></div>
    <div><label>Email</label><input type="email" name="email" required value="<?= e($u['email']??'') ?>" /></div>
    <div class="form-grid cols-2">
      <div><label>Rol</label>
        <select name="role">
          <?php foreach (['admin','author','professional','company'] as $r): ?><option value="<?= $r ?>" <?= ($u['role']??'')===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label>Estado</label>
        <select name="status">
          <?php foreach (['active','pending','suspended'] as $s): ?><option value="<?= $s ?>" <?= ($u['status']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div><label>Contraseña <?= $is_new?'(mínimo 8 caracteres)':'(dejar vacía para no cambiar)' ?></label><input type="password" name="password" minlength="8" <?= $is_new?'required':'' ?> /></div>
    <button class="btn" type="submit"><?= $is_new?'Crear':'Guardar' ?></button>
  </div>
</form>
<?php include __DIR__ . '/../_layout_end.php'; ?>
