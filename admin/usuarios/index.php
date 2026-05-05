<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Usuarios — Admin';
$me = auth_user();
if ($me['role'] !== 'admin') { http_response_code(403); exit('Solo admin'); }
$items = DB::all('SELECT id, email, name, role, status, last_login_at, created_at FROM users ORDER BY role, name');
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Usuarios</h1>
  <a href="<?= e(u('/admin/usuarios/edit.php')) ?>" class="btn">+ Nuevo usuario</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último login</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $u): ?>
    <tr>
      <td><?= e($u['name']) ?></td>
      <td style="color:#54636F;"><?= e($u['email']) ?></td>
      <td><?= e($u['role']) ?></td>
      <td><?= pill($u['status']) ?></td>
      <td style="color:#54636F;font-size:12px;"><?= e($u['last_login_at'] ?? '—') ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/usuarios/edit.php?id=' . (int)$u['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
