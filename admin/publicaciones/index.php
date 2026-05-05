<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Publicaciones — Admin';
$items = DB::all('SELECT p.*, pt.name type_name, d.name discipline_name FROM publications p LEFT JOIN publication_types pt ON pt.id=p.publication_type_id LEFT JOIN disciplines d ON d.id=p.discipline_id ORDER BY p.published_at DESC');
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Publicaciones</h1>
  <a href="<?= e(u('/admin/publicaciones/edit.php')) ?>" class="btn">+ Nueva</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Tipo</th><th>Disciplina</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $p): ?>
    <tr>
      <td><?= e($p['title']) ?></td>
      <td style="color:#54636F;"><?= e($p['type_name']) ?></td>
      <td style="color:#54636F;"><?= e($p['discipline_name']) ?></td>
      <td><?= pill($p['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/publicaciones/edit.php?id=' . (int)$p['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
