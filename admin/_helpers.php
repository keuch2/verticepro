<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image.php';
require_once __DIR__ . '/../includes/bootstrap.php';

function admin_back(string $path, string $flash_key = null, string $msg = null): void {
    if ($flash_key && $msg) flash($flash_key, $msg);
    redirect($path);
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
