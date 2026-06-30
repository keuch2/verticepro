<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image.php';
require_once __DIR__ . '/../includes/bootstrap.php';

function admin_back(string $path, string $flash_key = null, string $msg = null): void {
    if ($flash_key && $msg) flash($flash_key, $msg);
    redirect($path);
}

// Guarda el POST actual para repoblar el formulario tras un redirect de validación.
// Se usa junto a old() para que el editor no pierda lo que ya había escrito.
// IMPORTANTE: asegura la sesión de admin antes de tocar $_SESSION, porque en un
// GET la sesión aún no se ha iniciado (si no, se leería la sesión por defecto
// PHPSESSID en lugar de la de admin y el old input no se vería).
function flash_old(): void {
    _csrf_ensure_session();
    $_SESSION['old'] = $_POST;
}

// ¿Hay old input guardado de un redirect de validación previo?
function has_old(): bool {
    _csrf_ensure_session();
    return !empty($_SESSION['old']);
}

// Lee un valor del POST guardado por flash_old() con un fallback.
function old(string $key, $fallback = '') {
    _csrf_ensure_session();
    return $_SESSION['old'][$key] ?? $fallback;
}

// Limpia el old input. Llamar tras renderizar el formulario.
function old_clear(): void {
    _csrf_ensure_session();
    unset($_SESSION['old']);
}

// Shortcut: generate an <option> list
function opts(array $rows, string $value_key, string $label_key, $selected = null): string {
    $out = '';
    foreach ($rows as $r) {
        $v = (string)$r[$value_key];
        $s = ($selected !== null && (string)$selected === $v) ? ' selected' : '';
        $out .= '<option value="' . e($v) . '"' . $s . '>' . e($r[$label_key]) . '</option>';
    }
    return $out;
}

// Read a POST field with a default
function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function post_int(string $key): ?int {
    $v = $_POST[$key] ?? null;
    return ($v === null || $v === '') ? null : (int)$v;
}

function post_bool(string $key): int {
    return !empty($_POST[$key]) ? 1 : 0;
}

// Status pill HTML
function pill(string $status): string {
    return '<span class="status-pill ' . e($status) . '">' . e($status) . '</span>';
}
