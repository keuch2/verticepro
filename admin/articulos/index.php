<?php
require_once __DIR__ . '/../_helpers.php';
$page_title = 'Artículos — Admin';

$filters = [
    'status' => $_GET['status'] ?? '',
    'section' => $_GET['section'] ?? '',
    'q' => $_GET['q'] ?? '',
];
$items = ArticleRepo::all($filters, 200);

include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Artículos</h1>
  <a href="<?= e(u('/admin/articulos/edit.php')) ?>" class="btn">+ Nuevo artículo</a>
</div>
<form class="card" method="get" style="padding:14px;display:flex;gap:8px;align-items:center;">
  <input type="text" name="q" placeholder="Buscar por título..." value="<?= e($filters['q']) ?>" style="flex:1;" />
  <select name="status">
    <option value="">Todos los estados</option>
    <option value="published" <?= $filters['status']==='published'?'selected':'' ?>>Publicados</option>
    <option value="draft" <?= $filters['status']==='draft'?'selected':'' ?>>Borradores</option>
  </select>
  <select name="section">
    <option value="">Todas las secciones</option>
    <?= opts(SectionRepo::all(), 'slug', 'name', $filters['section']) ?>
  </select>
  <button class="btn secondary">Filtrar</button>
</form>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Sección</th><th>Estado</th><th>Publicado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $a): ?>
    <tr>
      <td>
        <?php if ($a['featured']): ?><span style="color:#F58220;font-size:11px;">★</span> <?php endif; ?>
        <?= e($a['title']) ?>
      </td>
      <td style="color:#54636F;"><?= e($a['section_name'] ?? '—') ?></td>
      <td><?= pill($a['status']) ?></td>
      <td style="color:#54636F;font-size:12px;"><?= e($a['published_at'] ?? '—') ?></td>
      <td style="text-align:right;white-space:nowrap;">
        <a href="<?= e(u('/articulo/' . $a['slug'])) ?>" target="_blank">Ver</a>
        &nbsp;·&nbsp;
        <a href="<?= e(u('/admin/articulos/edit.php?id=' . (int)$a['id'])) ?>">Editar</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_layout_end.php'; ?>
