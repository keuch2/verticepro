<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Eventos — Admin';
$items = EventRepo::adminAll();
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Eventos</h1>
  <a href="<?= e(u('/admin/eventos/edit.php')) ?>" class="btn">+ Nuevo evento</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Fecha</th><th>Disciplina</th><th>Modalidad</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $e): ?>
    <tr>
      <td><?= e($e['title']) ?></td>
      <td style="color:#54636F;"><?= e(format_date($e['starts_at'])) ?></td>
      <td style="color:#54636F;"><?= e($e['discipline_name'] ?? '—') ?></td>
      <td style="color:#54636F;"><?= e($e['modality'] ?? '—') ?></td>
      <td><?= pill($e['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/eventos/edit.php?id=' . (int)$e['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
