<?php
require_once __DIR__ . '/../includes/auth.php';
$admin_user = require_admin();
$page_title = $page_title ?? 'Admin — Vértice Pro';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(u('/admin/')) ?>assets/admin.css" />
</head>
<body class="admin-body">
  <aside class="sidebar">
    <div class="brand"><span>VÉRTICE</span><span class="brand-accent">PRO</span><em>admin</em></div>
    <nav>
      <a href="<?= e(u('/admin/')) ?>">Dashboard</a>
      <a href="<?= e(u('/admin/')) ?>articulos/">Artículos</a>
      <a href="<?= e(u('/admin/')) ?>profesionales/">Profesionales</a>
      <a href="<?= e(u('/admin/')) ?>empresas/">Organizaciones</a>
      <a href="<?= e(u('/admin/')) ?>bolsa/">Bolsa</a>
      <a href="<?= e(u('/admin/')) ?>eventos/">Eventos</a>
      <a href="<?= e(u('/admin/')) ?>publicaciones/">Publicaciones</a>
      <a href="<?= e(u('/admin/')) ?>recursos/">Recursos</a>
      <a href="<?= e(u('/admin/')) ?>clipping/">Clipping</a>
      <a href="<?= e(u('/admin/')) ?>aportes/">Aportes</a>
      <a href="<?= e(u('/admin/')) ?>taxonomias/">Taxonomías</a>
      <a href="<?= e(u('/admin/')) ?>usuarios/">Usuarios</a>
      <a href="<?= e(u('/admin/')) ?>publicidad/">Publicidad</a>
      <a href="<?= e(u('/admin/')) ?>configuracion/">Configuración</a>
    </nav>
    <div class="user-box">
      <div><strong><?= e($admin_user['name']) ?></strong></div>
      <div class="muted"><?= e($admin_user['role']) ?></div>
      <a href="<?= e(u('/admin/')) ?>logout.php" class="logout">Cerrar sesión</a>
    </div>
  </aside>
  <main class="content">
    <?php if ($m = flash('ok')): ?><div class="flash ok"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('err')): ?><div class="flash err"><?= e($m) ?></div><?php endif; ?>
