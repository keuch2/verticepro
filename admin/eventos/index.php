<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Eventos — Admin';
$items = DB::all('SELECT e.*, d.name AS discipline_name FROM events e LEFT JOIN disciplines d ON d.id = e.discipline_id ORDER BY (e.status = "draft") DESC, e.starts_at DESC');
$pending_count = (int)(DB::one("SELECT COUNT(*) n FROM events WHERE status='draft'")['n'] ?? 0);
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Eventos <?php if ($pending_count): ?><span style="background:#F58220;color:#fff;font-size:13px;padding:2px 10px;border-radius:999px;margin-left:8px;vertical-align:middle;"><?= $pending_count ?> pendiente<?= $pending_count===1?'':'s' ?> de revisión</span><?php endif; ?></h1>
  <a href="<?= e(u('/admin/eventos/edit.php')) ?>" class="btn">+ Nuevo evento</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Fecha</th><th>Disciplina</th><th>Propuesto por</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $ev): ?>
    <tr>
      <td><?= e($ev['title']) ?></td>
      <td style="color:#54636F;"><?= e(format_date($ev['starts_at'])) ?></td>
      <td style="color:#54636F;"><?= e($ev['discipline_name'] ?? '—') ?></td>
      <td style="color:#54636F;font-size:12px;">
        <?php if (!empty($ev['proposer_email'])): ?>
          <?= e($ev['proposer_name'] ?? '—') ?><br>
          <a href="mailto:<?= e($ev['proposer_email']) ?>"><?= e($ev['proposer_email']) ?></a>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
      <td><?= pill($ev['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/eventos/edit.php?id=' . (int)$ev['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
