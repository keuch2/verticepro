<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$token = $_GET['t'] ?? ($_POST['t'] ?? '');
$reset = $token ? password_reset_verify($token) : null;
$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$reset) {
        $errors[] = 'El enlace es inválido o expiró. Solicita uno nuevo.';
    } else {
        $pw1 = $_POST['password'] ?? '';
        $pw2 = $_POST['password_confirm'] ?? '';
        if (strlen($pw1) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($pw1 !== $pw2) {
            $errors[] = 'Las contraseñas no coinciden.';
        } else {
            DB::update('users', [
                'password_hash' => password_hash($pw1, PASSWORD_DEFAULT),
            ], ['id' => (int)$reset['user_id']]);
            password_reset_consume((int)$reset['id']);
            $done = true;
        }
    }
}

$page_title = 'Restablecer contraseña — Vértice Pro';
$page_active = 'restablecer-password.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-md mx-auto px-6 py-16">
  <h1 class="text-3xl font-extrabold">Restablecer contraseña</h1>

  <?php if (!$reset && !$done): ?>
    <div class="bg-red-50 border border-coral text-coral rounded p-4 mt-6 text-sm">
      El enlace es inválido o expiró. <a href="<?= e(u('/recuperar-password')) ?>" class="underline">Solicita uno nuevo</a>.
    </div>
  <?php elseif ($done): ?>
    <div class="bg-verde/10 border border-verde rounded p-4 mt-6 text-texto">
      ¡Listo! Tu contraseña fue actualizada. Ya puedes <a href="<?= e(u('/login')) ?>" class="text-azul hover:underline">iniciar sesión</a>.
    </div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="bg-red-50 border border-coral text-coral rounded p-4 mt-6 text-sm">
        <ul class="list-disc pl-5"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <input type="hidden" name="t" value="<?= e($token) ?>" />
      <div>
        <label class="block text-sm font-semibold mb-1">Nueva contraseña</label>
        <input name="password" type="password" required minlength="8" class="w-full border border-gray-300 rounded px-3 py-2" />
        <p class="text-xs text-gris-oscuro mt-1">Mínimo 8 caracteres.</p>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Confirmar contraseña</label>
        <input name="password_confirm" type="password" required minlength="8" class="w-full border border-gray-300 rounded px-3 py-2" />
      </div>
      <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition w-full" type="submit">Guardar contraseña</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
