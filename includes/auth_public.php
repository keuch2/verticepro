<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

/**
 * Helpers de autenticación para usuarios públicos (rol professional / company).
 * No bloquean acceso al sitio: las páginas deciden si requieren login.
 */

function require_public_user(): array {
    $u = auth_user();
    if (!$u || !in_array($u['role'], ['professional', 'company', 'admin', 'author'], true)) {
        $back = $_SERVER['REQUEST_URI'] ?? '/';
        $_SESSION['login_back_to'] = $back;
        redirect('/login.php');
    }
    return $u;
}

function require_professional(): array {
    $u = require_public_user();
    if (in_array($u['role'], ['admin', 'author'], true)) return $u;
    // Acepta al user si tiene un perfil profesional vinculado (independiente de users.role).
    $prof = DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
    if (!$prof) {
        // No tiene perfil profesional: redirige a la empresa o a inicio.
        $hasComp = DB::one('SELECT id FROM companies WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
        redirect($hasComp ? '/mi-empresa' : '/');
    }
    return $u;
}

function require_company(): array {
    $u = require_public_user();
    if (in_array($u['role'], ['admin', 'author'], true)) return $u;
    $comp = DB::one('SELECT id FROM companies WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
    if (!$comp) {
        $hasProf = DB::one('SELECT id FROM professionals WHERE user_id = ? LIMIT 1', [(int)$u['id']]);
        redirect($hasProf ? '/mi-perfil' : '/');
    }
    return $u;
}

/**
 * Devuelve la fila professional vinculada al usuario logueado (o null).
 */
function current_professional(): ?array {
    $u = auth_user();
    if (!$u || $u['role'] !== 'professional') return null;
    return DB::one('SELECT * FROM professionals WHERE user_id = ? LIMIT 1', [$u['id']]);
}

/**
 * Devuelve la fila company vinculada al usuario logueado (o null).
 */
function current_company(): ?array {
    $u = auth_user();
    if (!$u || $u['role'] !== 'company') return null;
    return DB::one('SELECT * FROM companies WHERE user_id = ? LIMIT 1', [$u['id']]);
}

/**
 * Genera y persiste un token de recuperación de password.
 * Devuelve el token en claro (para incluir en el email). El hash queda en BD.
 */
function password_reset_create(int $user_id, int $ttl_minutes = 60): string {
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    DB::insert('password_resets', [
        'user_id'    => $user_id,
        'token_hash' => $hash,
        'expires_at' => date('Y-m-d H:i:s', time() + $ttl_minutes * 60),
    ]);
    return $token;
}

/**
 * Verifica un token de recuperación. Devuelve la fila o null si inválido/expirado.
 */
function password_reset_verify(string $token): ?array {
    $hash = hash('sha256', $token);
    $row = DB::one('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1', [$hash]);
    return $row ?: null;
}

function password_reset_consume(int $reset_id): void {
    DB::update('password_resets', ['used_at' => date('Y-m-d H:i:s')], ['id' => $reset_id]);
}

/**
 * Header helper: ¿el usuario actual tiene sesión pública activa?
 */
function public_logged_in(): ?array {
    $u = auth_user();
    if (!$u) return null;
    if (!in_array($u['role'], ['professional', 'company', 'admin', 'author'], true)) return null;
    return $u;
}
