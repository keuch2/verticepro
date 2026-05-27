<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Empresas — Admin';
$items = DB::all("SELECT c.*, s.name sector_name, co.name country_name FROM companies c LEFT JOIN sectors s ON s.id=c.sector_id LEFT JOIN countries co ON co.id=c.country_id ORDER BY (c.status='pending') DESC, c.name");
$pending_count = (int)(DB::one("SELECT COUNT(*) n FROM companies WHERE status='pending'")['n'] ?? 0);
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Empresas <?php if ($pending_count): ?><span style="background:#F58220;color:#fff;font-size:13px;padding:2px 10px;border-radius:999px;margin-left:8px;vertical-align:middle;"><?= $pending_count ?> pendiente<?= $pending_count===1?'':'s' ?> de aprobación</span><?php endif; ?></h1>
  <a href="<?= e(u('/admin/empresas/edit.php')) ?>" class="btn">+ Nueva empresa</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Nombre</th><th>Sector</th><th>País</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $c): ?>
    <tr>
      <td><?= e($c['name']) ?> <?= $c['verified']?'<small style="color:#0078D4;">✓</small>':'' ?></td>
      <td style="color:#54636F;"><?= e($c['sector_name']) ?></td>
      <td style="color:#54636F;"><?= e($c['country_name']) ?></td>
      <td><?= pill($c['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/empresas/edit.php?id=' . (int)$c['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
