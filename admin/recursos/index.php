<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Recursos — Admin';
$items = DB::all('SELECT * FROM resources ORDER BY created_at DESC');
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Recursos</h1>
  <a href="<?= e(u('/admin/recursos/edit.php')) ?>" class="btn">+ Nuevo</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Categoría</th><th>Descargas</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $r): ?>
    <tr>
      <td><?= e($r['title']) ?></td>
      <td style="color:#54636F;"><?= e($r['category']) ?></td>
      <td style="color:#54636F;"><?= (int)$r['download_count'] ?></td>
      <td><?= pill($r['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/recursos/edit.php?id=' . (int)$r['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="5" style="padding:20px;text-align:center;color:#54636F;">Aún no hay recursos. Crea el primero.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
