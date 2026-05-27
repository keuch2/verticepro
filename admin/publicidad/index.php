<?php
require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../../includes/ads.php';
$page_title = 'Publicidad — Admin';
$items = Ads::all();

$slot_labels = [
    'header_top'        => 'Sobre el header',
    'home_hero'         => 'Bajo hero de inicio',
    'sidebar'           => 'Aside (secciones editoriales)',
    'between_articles'  => 'Entre artículos',
    'footer'            => 'Sobre el footer',
];
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Publicidad</h1>
  <a href="<?= e(u('/admin/publicidad/edit.php')) ?>" class="btn">+ Nuevo banner</a>
</div>
<p style="color:#54636F;margin-bottom:16px;font-size:14px;">Slots disponibles: <strong>Sobre el header</strong>, <strong>Bajo el hero de inicio</strong>, <strong>Aside</strong> (secciones editoriales), <strong>Entre artículos</strong>, <strong>Sobre el footer</strong>. Cada banner puede ser una imagen con link o HTML libre.</p>

<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Nombre</th><th>Slot</th><th>Período</th><th>Estado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $a):
      $now = time();
      $started = !$a['starts_at'] || strtotime($a['starts_at']) <= $now;
      $expired = $a['ends_at']    && strtotime($a['ends_at'])  <= $now;
      $live = $a['status'] === 'active' && $started && !$expired;
    ?>
    <tr>
      <td>
        <?= e($a['name']) ?>
        <?php if ($live): ?> <small style="color:#52B788;">● en vivo</small><?php endif; ?>
      </td>
      <td style="color:#54636F;"><?= e($slot_labels[$a['slot']] ?? $a['slot']) ?></td>
      <td style="color:#54636F;font-size:12px;">
        <?= $a['starts_at'] ? 'Desde ' . e(format_date($a['starts_at'])) : 'Sin fecha de inicio' ?><br>
        <?= $a['ends_at']   ? 'Hasta ' . e(format_date($a['ends_at']))   : 'Sin fecha de fin' ?>
      </td>
      <td><?= pill($a['status']) ?></td>
      <td style="text-align:right;"><a href="<?= e(u('/admin/publicidad/edit.php?id=' . (int)$a['id'])) ?>">Editar</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?>
    <tr><td colspan="5" style="text-align:center;color:#54636F;padding:24px;">No hay banners cargados.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
