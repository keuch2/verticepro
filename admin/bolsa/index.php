<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Bolsa — Admin';
$tab = $_GET['tab'] ?? 'ofertas';
$offers = DB::all('SELECT j.*, c.name company_name, co.name country_name FROM job_offers j JOIN companies c ON c.id=j.company_id LEFT JOIN countries co ON co.id=j.country_id ORDER BY j.published_at DESC');
$services = DB::all('SELECT s.*, p.name professional_name, co.name country_name FROM services s JOIN professionals p ON p.id=s.professional_id LEFT JOIN countries co ON co.id=s.country_id ORDER BY s.published_at DESC');
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Bolsa de Trabajo</h1>
  <div>
    <a href="<?= e(u('/admin/bolsa/edit_oferta.php')) ?>" class="btn">+ Oferta</a>
    <a href="<?= e(u('/admin/bolsa/edit_servicio.php')) ?>" class="btn">+ Servicio</a>
  </div>
</div>
<div class="card" style="padding:0;">
  <div style="padding:10px 14px;border-bottom:1px solid #e5e7eb;">
    <a href="?tab=ofertas" style="margin-right:16px;<?= $tab==='ofertas'?'font-weight:700;color:#F58220;':'' ?>">Ofertas (<?= count($offers) ?>)</a>
    <a href="?tab=servicios" style="<?= $tab==='servicios'?'font-weight:700;color:#F58220;':'' ?>">Servicios (<?= count($services) ?>)</a>
  </div>
<?php if ($tab === 'ofertas'): ?>
<table>
  <thead><tr><th>Título</th><th>Empresa</th><th>Modalidad</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($offers as $o): ?>
    <tr>
      <td><?= e($o['title']) ?></td>
      <td style="color:#54636F;"><?= e($o['company_name']) ?></td>
      <td style="color:#54636F;"><?= e($o['modality']) ?></td>
      <td><?= pill($o['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/bolsa/edit_oferta.php?id=' . (int)$o['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<table>
  <thead><tr><th>Título</th><th>Profesional</th><th>Modalidad</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($services as $s): ?>
    <tr>
      <td><?= e($s['title']) ?></td>
      <td style="color:#54636F;"><?= e($s['professional_name']) ?></td>
      <td style="color:#54636F;"><?= e($s['modality']) ?></td>
      <td><?= pill($s['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/bolsa/edit_servicio.php?id=' . (int)$s['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
