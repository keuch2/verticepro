<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/bolsa'); }
csrf_check();

$offer_id = (int)($_POST['offer_id'] ?? 0);
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$message  = trim($_POST['message'] ?? '');

if ($offer_id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('bolsa_err', 'Completa nombre y un email válido.');
    redirect('/bolsa');
}

$offer = DB::one(
    'SELECT j.*, c.name AS company_name, c.email AS company_email, c.user_id AS company_user_id, c.notifications_opt_in AS company_opt_in
     FROM job_offers j
     JOIN companies c ON c.id = j.company_id
     WHERE j.id = ? AND j.status = "published"',
    [$offer_id]
);
if (!$offer) {
    flash('bolsa_err', 'La oferta ya no está disponible.');
    redirect('/bolsa');
}

// Vincular professional_id si el email pertenece a un profesional registrado.
$prof = DB::one('SELECT id FROM professionals WHERE email = ? LIMIT 1', [$email]);
$prof_id = $prof['id'] ?? null;

// Duplicate guard: por (offer_id, guest_email)
$existing = DB::one('SELECT id FROM job_interests WHERE offer_id = ? AND guest_email = ? LIMIT 1', [$offer_id, $email]);
if ($existing) {
    flash('bolsa_ok', 'Ya habías marcado interés en esta oferta. ' . $offer['company_name'] . ' tiene tus datos.');
    redirect('/bolsa');
}

try {
    DB::insert('job_interests', [
        'offer_id'        => $offer_id,
        'user_id'         => null,
        'guest_name'      => $name,
        'guest_email'     => $email,
        'professional_id' => $prof_id ? (int)$prof_id : null,
        'message'         => $message ?: null,
    ]);
} catch (\Throwable $e) {
    flash('bolsa_err', 'No pudimos registrar tu interés. Inténtalo de nuevo.');
    redirect('/bolsa');
}

// Notificar al ofertante (empresa)
if (!empty($offer['company_email'])) {
    $title = 'Nuevo interesado en tu oferta: ' . $offer['title'];
    $body  = $name . ' marcó interés en tu oferta \"' . $offer['title'] . '\".' .
             ($message ? "\n\nMensaje: " . $message : '') .
             "\nContacto: " . $email;
    $link  = u('/bolsa-mis-ofertas');
    if (!empty($offer['company_user_id'])) {
        Notify::create((int)$offer['company_user_id'], 'new_interest', $title, $body, $link, $offer['company_email']);
    } else {
        Notify::emailOnly($offer['company_email'], $offer['company_name'], (int)($offer['company_opt_in'] ?? 1), $title, $body, $link);
    }
}

flash('bolsa_ok', '¡Listo! Marcamos tu interés en \"' . $offer['title'] . '\". La empresa recibirá tus datos.');
redirect('/bolsa');
