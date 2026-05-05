<?php
require_once __DIR__ . '/../includes/auth.php';
session_start_admin();

if (auth_user()) redirect('/admin/');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $u = auth_login($email, $pass);
    if ($u) redirect('/admin/');
    $error = 'Credenciales inválidas o demasiados intentos. Esperá 15 minutos si fallaste 5 veces.';
}
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Iniciar sesión · Vértice Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(u('/admin/assets/admin.css')) ?>" />
</head>
<body class="login-page">
  <div class="login-box">
    <div class="brand">VÉRTICE<span class="accent">PRO</span></div>
    <h1>Iniciar sesión</h1>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <div style="margin-bottom: 14px;">
        <label for="email">Correo electrónico</label>
        <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>" />
      </div>
      <div style="margin-bottom: 20px;">
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" required />
      </div>
      <button class="btn" style="width:100%;" type="submit">Entrar</button>
    </form>
    <p style="font-size:12px;color:#54636F;margin-top:16px;text-align:center;">Semilla: admin@verticepro.com / admin123</p>
  </div>
</body>
</html>
