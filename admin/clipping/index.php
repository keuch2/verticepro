<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Clipping — Admin';
$items = NewsClippingRepo::adminAll();
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Clipping de Noticias</h1>
  <a href="<?= e(u('/admin/clipping/edit.php')) ?>" class="btn">+ Nueva noticia</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Fuente</th><th>Disciplina</th><th>Publicada</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $n): ?>
    <tr>
      <td><?= e($n['title']) ?></td>
      <td style="color:#54636F;"><?= e($n['source_name']) ?></td>
      <td style="color:#54636F;"><?= e($n['discipline_name'] ?? '—') ?></td>
      <td style="color:#54636F;"><?= e(format_date($n['published_at'])) ?></td>
      <td><?= pill($n['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/clipping/edit.php?id=' . (int)$n['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
