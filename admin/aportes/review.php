<?php
require_once __DIR__ . '/../_helpers.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$a = $id ? DB::one('SELECT c.*, d.name discipline_name FROM user_contributions c LEFT JOIN disciplines d ON d.id = c.discipline_id WHERE c.id = ?', [$id]) : null;
if (!$a) { http_response_code(404); echo 'Aporte no encontrado'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $notes  = trim(post('review_notes','')) ?: null;
    $next   = null;

    if ($action === 'approve')      $next = 'approved';
    elseif ($action === 'reject')   $next = 'rejected';

    if ($next) {
        DB::update('user_contributions', [
            'status'       => $next,
            'review_notes' => $notes,
            'reviewed_by'  => (int)$admin_user['id'],
            'reviewed_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        // Si se aprueba, publicar el aporte como resource para que aparezca en biblioteca.
        if ($next === 'approved') {
            try {
                DB::insert('resources', [
                    'slug'         => slugify($a['title']) . '-' . $a['id'],
                    'title'        => $a['title'],
                    'description'  => $a['description'] ?: ($a['guest_name'] ? 'Aporte de ' . $a['guest_name'] : null),
                    'file_path'    => $a['file_path'],
                    'category'     => $a['category'],
                    'status'       => 'published',
                ]);
            } catch (\Throwable $e) {}
        }

        // Notificar al autor
        if (!empty($a['guest_email'])) {
            $title = $next === 'approved' ? '¡Tu aporte fue publicado en Vértice Pro!' : 'Sobre tu aporte a Vértice Pro';
            $body  = $next === 'approved'
                ? 'Aprobamos tu aporte "' . $a['title'] . '". Ya está disponible en la sección de Recursos. ¡Gracias por tu colaboración!'
                : 'Revisamos tu aporte "' . $a['title'] . '" pero no podremos publicarlo en esta ocasión.' . ($notes ? "\n\nNotas: $notes" : '');
            Notify::emailOnly($a['guest_email'], (string)($a['guest_name'] ?? ''), 1, $title, $body, $next === 'approved' ? u('/recursos') : null);
        }

        flash('ok', 'Aporte ' . ($next === 'approved' ? 'aprobado' : 'rechazado'));
        redirect('/admin/aportes/');
    }
}

$page_title = 'Revisar aporte';
include __DIR__ . '/../_layout.php';
?>
<div class="toolbar">
  <h1 style="margin:0;">Revisar aporte</h1>
  <a href="<?= e(u('/admin/aportes/')) ?>" class="btn secondary">Volver</a>
</div>

<div class="card">
  <h2 style="margin-top:0;"><?= e($a['title']) ?></h2>
  <p><strong>Autor:</strong> <?= e($a['guest_name'] ?? '—') ?> · <a href="mailto:<?= e($a['guest_email']) ?>"><?= e($a['guest_email']) ?></a></p>
  <p><strong>Disciplina:</strong> <?= e($a['discipline_name'] ?? '—') ?></p>
  <p><strong>Categoría:</strong> <?= e($a['category'] ?? '—') ?></p>
  <p><strong>Recibido:</strong> <?= e(format_date($a['created_at'])) ?></p>
  <p><strong>Archivo:</strong> <a href="<?= e(u('/' . $a['file_path'])) ?>" target="_blank">Descargar (<?= round((int)$a['file_size']/1024) ?> KB · <?= e($a['file_mime']) ?>)</a></p>
  <?php if ($a['description']): ?><p><strong>Descripción:</strong><br><?= nl2br(e($a['description'])) ?></p><?php endif; ?>
  <p><strong>Estado actual:</strong> <?= pill($a['status']) ?></p>
  <?php if ($a['review_notes']): ?><p><strong>Notas previas:</strong><br><?= nl2br(e($a['review_notes'])) ?></p><?php endif; ?>
</div>

<?php if ($a['status'] === 'pending'): ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" />
  <div class="form-grid">
    <div><label>Notas (opcional, se incluyen en el email al autor si se rechaza)</label><textarea name="review_notes" rows="3"></textarea></div>
    <div style="display:flex;gap:12px;">
      <button name="action" value="approve" class="btn" type="submit">Aprobar y publicar en recursos</button>
      <button name="action" value="reject" class="btn danger" type="submit">Rechazar</button>
    </div>
  </div>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../_layout_end.php'; ?>
