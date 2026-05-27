<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_public.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/bolsa'); }
csrf_check();

$offer_id = (int)($_POST['offer_id'] ?? 0);
$action   = $_POST['action'] ?? '';

if ($offer_id <= 0) {
    flash('bolsa_err', 'Oferta inválida.');
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

$user = public_logged_in();

// =========================================================
// Flujo 1: usuario LOGUEADO — toggle favorito (sin formulario)
// =========================================================
if ($user && in_array($action, ['favorite', 'unfavorite'], true)) {
    if ($action === 'unfavorite') {
        DB::run('DELETE FROM job_interests WHERE offer_id = ? AND user_id = ?', [$offer_id, (int)$user['id']]);
        flash('bolsa_ok', 'Quitamos esa oferta de tus intereses.');
        redirect('/bolsa');
    }

    // favorite
    $existing = DB::one('SELECT id FROM job_interests WHERE offer_id = ? AND user_id = ? LIMIT 1', [$offer_id, (int)$user['id']]);
    if ($existing) {
        flash('bolsa_ok', 'Ya tenías esta oferta entre tus intereses.');
        redirect('/bolsa');
    }

    // Buscar professional_id si el user es professional con perfil
    $prof_id = null;
    if ($user['role'] === 'professional') {
        $prof = DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$user['id']]);
        $prof_id = $prof['id'] ?? null;
    }

    try {
        DB::insert('job_interests', [
            'offer_id'        => $offer_id,
            'user_id'         => (int)$user['id'],
            'guest_name'      => $user['name'] ?? null,
            'guest_email'     => $user['email'] ?? null,
            'professional_id' => $prof_id ? (int)$prof_id : null,
            'message'         => null,
        ]);
    } catch (\Throwable $e) {
        flash('bolsa_err', 'No pudimos guardar tu interés.');
        redirect('/bolsa');
    }

    // Notificar a la empresa
    if (!empty($offer['company_email'])) {
        $title = 'Nuevo interesado en tu oferta: ' . $offer['title'];
        $body  = ($user['name'] ?? 'Un profesional') . ' marcó interés en tu oferta "' . $offer['title'] . '".' .
                 "\nContacto: " . ($user['email'] ?? '');
        $link  = u('/bolsa-mis-ofertas');
        if (!empty($offer['company_user_id'])) {
            Notify::create((int)$offer['company_user_id'], 'new_interest', $title, $body, $link, $offer['company_email']);
        } else {
            Notify::emailOnly($offer['company_email'], $offer['company_name'], (int)($offer['company_opt_in'] ?? 1), $title, $body, $link);
        }
    }

    flash('bolsa_ok', 'Agregamos "' . $offer['title'] . '" a tus intereses. La encontrarás en /mi-perfil.');
    redirect('/bolsa');
}

// =========================================================
// Flujo 2: invitado (formulario expandible con nombre + email)
// =========================================================
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$message  = trim($_POST['message'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('bolsa_err', 'Completa nombre y un email válido.');
    redirect('/bolsa');
}

// Vincular professional_id si el email pertenece a un profesional registrado.
$prof = DB::one('SELECT id FROM professionals WHERE email = ? LIMIT 1', [$email]);
$prof_id = $prof['id'] ?? null;

// Duplicate guard por (offer_id, guest_email)
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

if (!empty($offer['company_email'])) {
    $title = 'Nuevo interesado en tu oferta: ' . $offer['title'];
    $body  = $name . ' marcó interés en tu oferta "' . $offer['title'] . '".' .
             ($message ? "\n\nMensaje: " . $message : '') .
             "\nContacto: " . $email;
    $link  = u('/bolsa-mis-ofertas');
    if (!empty($offer['company_user_id'])) {
        Notify::create((int)$offer['company_user_id'], 'new_interest', $title, $body, $link, $offer['company_email']);
    } else {
        Notify::emailOnly($offer['company_email'], $offer['company_name'], (int)($offer['company_opt_in'] ?? 1), $title, $body, $link);
    }
}

flash('bolsa_ok', '¡Listo! Marcamos tu interés en "' . $offer['title'] . '". La empresa recibirá tus datos.');
redirect('/bolsa');
