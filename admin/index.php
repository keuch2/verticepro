<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Dashboard — Admin Vértice Pro';
include __DIR__ . '/_layout.php';

$counts = [
    'articles'      => DB::one("SELECT COUNT(*) c FROM articles")['c'],
    'articles_pub'  => DB::one("SELECT COUNT(*) c FROM articles WHERE status='published'")['c'],
    'professionals' => DB::one("SELECT COUNT(*) c FROM professionals WHERE status='active'")['c'],
    'companies'     => DB::one("SELECT COUNT(*) c FROM companies WHERE status='active'")['c'],
    'jobs'          => DB::one("SELECT COUNT(*) c FROM job_offers WHERE status='published'")['c'],
    'services'      => DB::one("SELECT COUNT(*) c FROM services WHERE status='published'")['c'],
    'publications'  => DB::one("SELECT COUNT(*) c FROM publications WHERE status='published'")['c'],
    'users'         => DB::one("SELECT COUNT(*) c FROM users")['c'],
];
$recent = DB::all("SELECT id, slug, title, status, updated_at FROM articles ORDER BY updated_at DESC LIMIT 10");
?>
<h1>Dashboard</h1>

<div class="stats-grid">
  <div class="stat"><div class="label">Artículos publicados</div><div class="value"><?= (int)$counts['articles_pub'] ?></div></div>
  <div class="stat"><div class="label">Artículos totales</div><div class="value"><?= (int)$counts['articles'] ?></div></div>
  <div class="stat"><div class="label">Profesionales</div><div class="value"><?= (int)$counts['professionals'] ?></div></div>
  <div class="stat"><div class="label">Empresas</div><div class="value"><?= (int)$counts['companies'] ?></div></div>
  <div class="stat"><div class="label">Ofertas activas</div><div class="value"><?= (int)$counts['jobs'] ?></div></div>
  <div class="stat"><div class="label">Servicios</div><div class="value"><?= (int)$counts['services'] ?></div></div>
  <div class="stat"><div class="label">Publicaciones</div><div class="value"><?= (int)$counts['publications'] ?></div></div>
  <div class="stat"><div class="label">Usuarios</div><div class="value"><?= (int)$counts['users'] ?></div></div>
</div>

<h2>Artículos recientes</h2>
<div class="card" style="padding:0;">
<table>
  <thead><tr><th>Título</th><th>Estado</th><th>Actualizado</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($recent as $a): ?>
    <tr>
      <td><?= e($a['title']) ?></td>
      <td><span class="status-pill <?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
      <td style="color:#54636F;font-size:12px;"><?= e($a['updated_at']) ?></td>
      <td><a href="<?= e(u('/admin/articulos/edit.php?id=' . (int)$a['id'])) ?>">Editar →</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
