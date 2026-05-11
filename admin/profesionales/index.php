<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Profesionales — Admin';
$items = DB::all("SELECT p.*, c.name city_name, co.name country_name FROM professionals p LEFT JOIN cities c ON c.id=p.city_id LEFT JOIN countries co ON co.id=c.country_id ORDER BY (p.status='pending') DESC, p.featured DESC, p.name");
$pending_count = (int)(DB::one("SELECT COUNT(*) n FROM professionals WHERE status='pending'")['n'] ?? 0);
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Profesionales <?php if ($pending_count): ?><span style="background:#F58220;color:#fff;font-size:13px;padding:2px 10px;border-radius:999px;margin-left:8px;vertical-align:middle;"><?= $pending_count ?> pendiente<?= $pending_count===1?'':'s' ?> de aprobación</span><?php endif; ?></h1>
  <a href="<?= e(u('/admin/profesionales/edit.php')) ?>" class="btn">+ Nuevo profesional</a>
</div>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Nombre</th><th>Título</th><th>Ubicación</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $p): ?>
    <tr>
      <td><?= $p['featured']?'<span style="color:#F58220;">★</span> ':'' ?><?= e($p['name']) ?> <?= $p['verified']?'<small style="color:#0078D4;">✓</small>':'' ?></td>
      <td style="color:#54636F;"><?= e($p['title']) ?></td>
      <td style="color:#54636F;"><?= e(trim(($p['city_name']??'').($p['country_name']?', '.$p['country_name']:''),', ')) ?></td>
      <td><?= pill($p['status']) ?></td>
      <td style="text-align:right;white-space:nowrap;">
        <a href="<?= e(u('/perfil/' . $p['slug'])) ?>" target="_blank">Ver</a> · <a href="<?= e(u('/admin/profesionales/edit.php?id=' . (int)$p['id'])) ?>">Editar</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
