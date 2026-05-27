<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/eventos'); }
csrf_check();

$proposer_name  = trim($_POST['proposer_name']  ?? '');
$proposer_email = trim($_POST['proposer_email'] ?? '');
$title          = trim($_POST['title']          ?? '');
$description    = trim($_POST['description']    ?? '');
$starts_at      = trim($_POST['starts_at']      ?? '');
$ends_at        = trim($_POST['ends_at']        ?? '');
$modality       = $_POST['modality']           ?? null;
$discipline_id  = !empty($_POST['discipline_id']) ? (int)$_POST['discipline_id'] : null;
$location       = trim($_POST['location']      ?? '');
$url            = trim($_POST['url']           ?? '');

$errors = [];
if ($proposer_name === '')                                       $errors[] = 'Indica tu nombre.';
if (!filter_var($proposer_email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Email inválido.';
if ($title === '')                                               $errors[] = 'Indica un título.';
if ($starts_at === '' || !strtotime($starts_at))                 $errors[] = 'Indica una fecha y hora válida.';
if (!in_array($modality, ['presencial','virtual','hibrido', null, ''], true)) $errors[] = 'Modalidad inválida.';
if ($url !== '' && !preg_match('#^https?://#i', $url))            $errors[] = 'La URL debe empezar con http(s)://';

if ($errors) {
    flash('eventos_err', implode(' · ', $errors));
    redirect('/eventos');
}

$base = slugify($title) ?: 'evento';
$slug = $base; $i = 2;
while (DB::one('SELECT id FROM events WHERE slug = ? LIMIT 1', [$slug])) {
    $slug = $base . '-' . $i++;
    if ($i > 999) { $slug = $base . '-' . bin2hex(random_bytes(3)); break; }
}

try {
    DB::insert('events', [
        'slug'           => $slug,
        'title'          => $title,
        'description'    => $description ?: null,
        'starts_at'      => date('Y-m-d H:i:s', strtotime($starts_at)),
        'ends_at'        => $ends_at ? date('Y-m-d H:i:s', strtotime($ends_at)) : null,
        'modality'       => $modality ?: null,
        'location'       => $location ?: null,
        'url'            => $url ?: null,
        'proposer_name'  => $proposer_name,
        'proposer_email' => $proposer_email,
        'discipline_id'  => $discipline_id,
        'status'         => 'draft',
    ]);
} catch (\Throwable $e) {
    flash('eventos_err', 'No pudimos registrar tu propuesta. Inténtalo de nuevo.');
    redirect('/eventos');
}

// Notificar al equipo admin (settings.contact.email si existe).
$contact = function_exists('setting') ? setting('contact.email', '') : '';
if ($contact && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
    Notify::emailOnly(
        $contact,
        'Vértice Pro',
        1,
        'Nueva propuesta de evento pendiente de revisión',
        "Recibimos una propuesta de evento desde el sitio.\n\n"
            . "Título: $title\n"
            . "Fecha: " . date('d/m/Y H:i', strtotime($starts_at)) . "\n"
            . "Propone: $proposer_name <$proposer_email>\n"
            . ($location ? "Lugar: $location\n" : '')
            . ($url ? "URL: $url\n" : '')
            . ($description ? "\n$description" : ''),
        u('/admin/eventos/')
    );
}

flash('eventos_ok', '¡Gracias por proponer un evento! Quedó pendiente de revisión. Te avisaremos por email cuando se publique.');
redirect('/eventos');
