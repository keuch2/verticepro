<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';

// Usar el mismo nombre/params de sesión que auth.php (no el PHPSESSID por defecto),
// para que la sesión creada al loguear sea la misma que auth_user() luego lee.
session_start_admin();

// Si ya está logueado, redirigir a su área
$u = auth_user();
if ($u) {
    if ($u['role'] === 'professional') redirect('/mi-perfil');
    if ($u['role'] === 'company')      redirect('/mi-organizacion');
    if (in_array($u['role'], ['admin','author'], true)) redirect('/admin/');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Ingresa tu email y contraseña.';
    } else {
        $res = auth_login_ex($email, $password);
        $user = $res['user'];
        if (!$user) {
            switch ($res['reason']) {
                case 'rate_limited':
                    $errors[] = 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.';
                    break;
                case 'pending':
                    $errors[] = 'Tu cuenta todavía está pendiente de aprobación. Te avisaremos por email cuando esté activa y podrás iniciar sesión.';
                    break;
                case 'suspended':
                    $errors[] = 'Tu cuenta está suspendida. Escríbenos si crees que es un error.';
                    break;
                default:
                    $errors[] = 'Credenciales incorrectas.';
            }
        } else {
            $back = $_SESSION['login_back_to'] ?? null;
            unset($_SESSION['login_back_to']);
            if ($back && preg_match('#^/[a-z0-9\-/_\?\=\&\.]*$#i', $back)) {
                redirect($back);
            }
            if ($user['role'] === 'professional')          redirect('/mi-perfil');
            elseif ($user['role'] === 'company')           redirect('/mi-organizacion');
            elseif (in_array($user['role'], ['admin','author'], true)) redirect('/admin/');
            else                                            redirect('/');
        }
    }
}

$page_title = 'Iniciar sesión — Vértice Pro';
$page_active = 'login.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-md mx-auto px-6 py-16">
  <h1 class="text-3xl font-extrabold">Iniciar sesión</h1>
  <p class="text-gris-oscuro mt-2">Accede a tu cuenta para editar tu perfil profesional o de organización.</p>

  <?php if ($errors): ?>
    <div class="bg-red-50 border border-coral text-coral rounded p-4 mt-6 text-sm">
      <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-4">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
    <div>
      <label class="block text-sm font-semibold mb-1">Email</label>
      <input name="email" type="email" required value="<?= e($email) ?>" class="w-full border border-gray-300 rounded px-3 py-2" autofocus />
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Contraseña</label>
      <input name="password" type="password" required class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>
    <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition w-full" type="submit">Iniciar sesión</button>
    <div class="flex justify-between text-sm pt-2">
      <a href="<?= e(u('/recuperar-password')) ?>" class="text-azul hover:underline">¿Olvidaste tu contraseña?</a>
      <a href="<?= e(u('/registro')) ?>" class="text-azul hover:underline">Crear cuenta</a>
    </div>
  </form>

  <p class="text-xs text-gris-oscuro text-center mt-4">
    ¿Eres organización? <a href="<?= e(u('/registro-organizacion')) ?>" class="text-azul hover:underline">Regístrate aquí</a>.
  </p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
