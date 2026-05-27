<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

$u = auth_user();
if (!$u) {
    // No hay login público todavía: invitamos al usuario a iniciar sesión vía admin.
    $page_title = 'Notificaciones — Vértice Pro';
    $page_active = 'notificaciones.php';
    include __DIR__ . '/includes/header.php';
    ?>
    <section class="max-w-3xl mx-auto px-6 py-20 text-center">
      <h1 class="text-3xl font-extrabold mb-4">Notificaciones</h1>
      <p class="text-gris-oscuro mb-6">Para ver tu bandeja de notificaciones necesitas iniciar sesión.</p>
      <a href="<?= e(u('/admin/login.php')) ?>" class="bg-naranja text-white font-semibold px-6 py-2.5 rounded hover:bg-orange-600 transition">Iniciar sesión</a>
    </section>
    <?php
    include __DIR__ . '/includes/footer.php';
    return;
}

// Marcar como leída (acción puntual)
if (($_GET['mark'] ?? '') !== '' && ctype_digit($_GET['mark'])) {
    Notify::markRead((int)$_GET['mark'], (int)$u['id']);
    redirect('/notificaciones');
}
if (($_POST['mark_all'] ?? '') === '1') {
    csrf_check();
    Notify::markAllRead((int)$u['id']);
    redirect('/notificaciones');
}

$items = Notify::listFor((int)$u['id'], 100);
$unread = Notify::unreadCount((int)$u['id']);

$page_title = 'Notificaciones — Vértice Pro';
$page_active = 'notificaciones.php';
include __DIR__ . '/includes/header.php';
?>
<section class="max-w-4xl mx-auto px-6 py-14">
  <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold">Notificaciones</h1>
      <p class="text-gris-oscuro mt-1">Actividad relevante de tu perfil y tus interacciones en la plataforma.</p>
    </div>
    <?php if ($unread): ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
        <input type="hidden" name="mark_all" value="1" />
        <button class="text-sm text-azul font-semibold hover:underline" type="submit">Marcar todas como leídas (<?= $unread ?>)</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$items): ?>
    <div class="bg-gris-claro border border-gray-200 rounded p-6 text-center text-gris-oscuro">
      No tienes notificaciones todavía.
    </div>
  <?php else: ?>
    <ul class="space-y-3">
      <?php foreach ($items as $n): $isUnread = empty($n['read_at']); ?>
        <li class="bg-white rounded-lg border <?= $isUnread ? 'border-naranja' : 'border-gray-200' ?> p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <?php if ($isUnread): ?><span class="inline-block w-2 h-2 rounded-full bg-naranja"></span><?php endif; ?>
                <h3 class="font-bold text-texto"><?= e($n['title']) ?></h3>
              </div>
              <?php if ($n['body']): ?><p class="text-sm text-gris-oscuro mt-1"><?= nl2br(e($n['body'])) ?></p><?php endif; ?>
              <p class="text-xs text-gris-oscuro opacity-70 mt-2"><?= e(format_date($n['created_at'])) ?></p>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
              <?php if ($n['link']): ?>
                <a href="<?= e($n['link']) ?>" class="text-sm text-naranja font-semibold hover:underline">Ver →</a>
              <?php endif; ?>
              <?php if ($isUnread): ?>
                <a href="?mark=<?= (int)$n['id'] ?>" class="text-xs text-azul hover:underline">Marcar leída</a>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
