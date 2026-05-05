<?php

function cfg(): array {
    static $c = null;
    if ($c === null) $c = require __DIR__ . '/config.php';
    return $c;
}

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string {
    return rtrim(cfg()['base_path'] ?? '', '/');
}

function u(string $path = ''): string {
    return base_path() . '/' . ltrim($path, '/');
}

function url(string $path = ''): string {
    $base = rtrim(cfg()['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string {
    return u($path);
}

function img_url(string $rel): string {
    if ($rel === '' || $rel === null) return '';
    if (preg_match('#^https?://#i', $rel)) return $rel;
    return base_path() . '/' . ltrim(cfg()['img_url'], '/') . '/' . ltrim($rel, '/');
}

function slugify(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function redirect(string $path): void {
    // If path is already absolute (http...) or already includes the base path, use as-is.
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }
    $bp = base_path();
    if ($bp && !str_starts_with($path, $bp . '/') && $path !== $bp) {
        $path = $bp . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

function current_page_filename(): string {
    $s = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
    return basename($s);
}

function format_date(?string $datetime, string $fmt = 'd \d\e F, Y'): string {
    if (!$datetime) return '';
    $months = [
        '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
        '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
        '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre',
    ];
    $t = strtotime($datetime);
    if (!$t) return '';
    $m = $months[date('m', $t)] ?? date('M', $t);
    return date('d', $t) . " de $m, " . date('Y', $t);
}

function _csrf_ensure_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (function_exists('session_start_admin')) { session_start_admin(); }
        else { session_start(); }
    }
}

function csrf_token(): string {
    _csrf_ensure_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    _csrf_ensure_session();
    $token = $_POST['csrf'] ?? $_GET['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token inválido');
    }
}

function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
