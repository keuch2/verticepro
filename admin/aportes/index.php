<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Aportes — Admin';
$items = DB::all("SELECT c.*, d.name discipline_name FROM user_contributions c LEFT JOIN disciplines d ON d.id = c.discipline_id ORDER BY (c.status='pending') DESC, c.created_at DESC");
$pending = (int)(DB::one("SELECT COUNT(*) n FROM user_contributions WHERE status='pending'")['n'] ?? 0);
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Aportes de la comunidad <?php if ($pending): ?><span style="background:#F58220;color:#fff;font-size:13px;padding:2px 10px;border-radius:999px;margin-left:8px;"><?= $pending ?> pendiente<?= $pending===1?'':'s' ?></span><?php endif; ?></h1>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Autor</th><th>Disciplina</th><th>Archivo</th><th>Recibido</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $a): ?>
    <tr>
      <td><?= e($a['title']) ?></td>
      <td style="color:#54636F;"><?= e($a['guest_name'] ?? '—') ?><br><small><?= e($a['guest_email'] ?? '') ?></small></td>
      <td style="color:#54636F;"><?= e($a['discipline_name'] ?? '—') ?></td>
      <td><a href="<?= e(u('/' . $a['file_path'])) ?>" target="_blank">Descargar</a></td>
      <td style="color:#54636F;"><?= e(format_date($a['created_at'])) ?></td>
      <td><?= pill($a['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/aportes/review.php?id=' . (int)$a['id'])) ?>">Revisar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
