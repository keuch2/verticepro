<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$sent = false;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Ingresa un email válido.';
    } else {
        // Buscamos en users primero
        $user = DB::one('SELECT id, name, email, role, status FROM users WHERE email = ? LIMIT 1', [$email]);

        // Si no hay user pero sí hay un professional / company activo con ese email,
        // creamos el user automáticamente vinculándolo (flujo de "reclamar perfil").
        if (!$user) {
            $prof = DB::one('SELECT id, name, email FROM professionals WHERE email = ? AND status = "active" LIMIT 1', [$email]);
            $comp = DB::one('SELECT id, name, email FROM companies WHERE email = ? AND status = "active" LIMIT 1', [$email]);
            if ($prof) {
                $uid = DB::insert('users', [
                    'email' => $prof['email'],
                    'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'role' => 'professional',
                    'name' => $prof['name'],
                    'status' => 'active',
                ]);
                DB::update('professionals', ['user_id' => $uid], ['id' => $prof['id']]);
                $user = DB::one('SELECT id, name, email, role, status FROM users WHERE id = ?', [$uid]);
            } elseif ($comp) {
                $uid = DB::insert('users', [
                    'email' => $comp['email'],
                    'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'role' => 'company',
                    'name' => $comp['name'],
                    'status' => 'active',
                ]);
                DB::update('companies', ['user_id' => $uid], ['id' => $comp['id']]);
                $user = DB::one('SELECT id, name, email, role, status FROM users WHERE id = ?', [$uid]);
            }
        }

        // Solo enviamos si el user existe y está activo. Pero al usuario siempre le mostramos "enviado".
        if ($user && $user['status'] === 'active') {
            $token = password_reset_create((int)$user['id'], 60);
            $link = u('/restablecer-password?t=' . $token);
            Notify::emailOnly(
                $user['email'],
                $user['name'],
                1,
                'Recupera tu acceso a Vértice Pro',
                "Recibimos una solicitud para restablecer tu contraseña.\n\nHaz clic en el siguiente enlace (válido por 1 hora) para definir una nueva contraseña:",
                $link
            );
        }
        $sent = true;
    }
}

$page_title = 'Recuperar contraseña — Vértice Pro';
$page_active = 'recuperar-password.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-md mx-auto px-6 py-16">
  <h1 class="text-3xl font-extrabold">Recuperar contraseña</h1>
  <p class="text-gris-oscuro mt-2">Te enviaremos un enlace para que definas una nueva contraseña.</p>

  <?php if ($err): ?>
    <div class="bg-red-50 border border-coral text-coral rounded p-4 mt-6 text-sm"><?= e($err) ?></div>
  <?php endif; ?>

  <?php if ($sent): ?>
    <div class="bg-verde/10 border border-verde rounded p-4 mt-6 text-texto">
      Si el email coincide con una cuenta o perfil registrado, te enviamos un enlace. Revisa tu bandeja de entrada (y la carpeta de spam).
    </div>
    <div class="mt-4"><a href="<?= e(u('/login')) ?>" class="text-azul hover:underline">Volver al inicio de sesión</a></div>
  <?php else: ?>
    <form method="post" class="bg-white border border-gray-200 rounded-lg p-6 mt-6 space-y-4">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
      <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input name="email" type="email" required class="w-full border border-gray-300 rounded px-3 py-2" autofocus />
      </div>
      <button class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition w-full" type="submit">Enviar enlace</button>
      <div class="text-sm pt-2"><a href="<?= e(u('/login')) ?>" class="text-azul hover:underline">Volver</a></div>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
