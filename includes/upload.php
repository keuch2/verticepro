<?php
require_once __DIR__ . '/helpers.php';

/**
 * Upload a file (PDF / Office docs) to /uploads/<folder>/.
 * Returns array ['path' => relative path from project root, 'size' => bytes, 'mime' => mime]
 * or null on failure.
 */
function upload_doc(array $file, string $folder, string $slug_prefix, int $max_mb = 15): ?array {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $max_mb * 1024 * 1024) return null;

    $allowed = [
        'application/pdf'                                                       => 'pdf',
        'application/msword'                                                    => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel'                                              => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'     => 'xlsx',
        'text/csv'                                                              => 'csv',
        'image/jpeg'                                                            => 'jpg',
        'image/png'                                                             => 'png',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) return null;

    $ext = $allowed[$mime];
    $base = slugify($slug_prefix) ?: 'doc';
    $filename = $base . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

    $root = dirname(__DIR__);
    $dir  = $root . '/uploads/' . trim($folder, '/');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    return [
        'path' => 'uploads/' . trim($folder, '/') . '/' . $filename,
        'size' => (int)$file['size'],
        'mime' => $mime,
    ];
}
