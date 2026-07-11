<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

function session_start_admin(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(cfg()['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function auth_user(): ?array {
    session_start_admin();
    if (empty($_SESSION['user_id'])) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $u = DB::one('SELECT id, email, role, name, status FROM users WHERE id = ?', [$_SESSION['user_id']]);
    if (!$u || $u['status'] !== 'active') {
        auth_logout();
        return null;
    }
    return $cached = $u;
}

function require_admin(): array {
    $u = auth_user();
    if (!$u || !in_array($u['role'], ['admin', 'author'], true)) {
        redirect('/admin/login.php');
    }
    return $u;
}

function require_role(string ...$roles): array {
    $u = auth_user();
    if (!$u || !in_array($u['role'], $roles, true)) {
        redirect('/admin/login.php');
    }
    return $u;
}

/**
 * Intenta autenticar. Devuelve ['user'=>?array, 'reason'=>string].
 * reason ∈ {'ok','rate_limited','invalid','pending','suspended'}.
 * El estado (pending/suspended) solo se revela cuando la CONTRASEÑA es correcta,
 * para no permitir enumeración de emails con credenciales al azar.
 */
function auth_login_ex(string $email, string $password): array {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $recent = DB::one(
        "SELECT COUNT(*) c FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > NOW() - INTERVAL 15 MINUTE",
        [$ip]
    );
    if (($recent['c'] ?? 0) >= 5) return ['user' => null, 'reason' => 'rate_limited'];

    $u = DB::one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    $pw_ok = $u && password_verify($password, $u['password_hash']);
    $active = $u && $u['status'] === 'active';
    $ok = $pw_ok && $active;
    DB::insert('login_attempts', ['ip' => $ip, 'email' => $email, 'success' => $ok ? 1 : 0]);

    if (!$pw_ok)  return ['user' => null, 'reason' => 'invalid'];
    if (!$active) return ['user' => null, 'reason' => $u['status'] === 'pending' ? 'pending' : 'suspended'];

    session_start_admin();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $u['id'];
    DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], ['id' => $u['id']]);
    return ['user' => $u, 'reason' => 'ok'];
}

/** Compatibilidad: devuelve el user o null (sin la razón). */
function auth_login(string $email, string $password): ?array {
    return auth_login_ex($email, $password)['user'];
}

function auth_logout(): void {
    session_start_admin();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
