<?php
require_once __DIR__ . '/helpers.php';

/**
 * Upload an image file. Returns relative path from /img root (e.g. "articles/foo-123.jpg"),
 * or null on failure.
 */
function upload_image(array $file, string $folder, string $slug_prefix, int $thumb_w = 400): ?string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) return null;

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) return null;
    if ($file['size'] > 8 * 1024 * 1024) return null;

    $ext = $allowed[$mime];
    $base = slugify($slug_prefix) ?: 'img';
    $filename = $base . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;

    $img_root = cfg()['img_path'];
    $dir = $img_root . '/' . trim($folder, '/');
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    // Generate thumbnail
    make_thumbnail($dest, $dir . '/thumb-' . $filename, $thumb_w);

    return trim($folder, '/') . '/' . $filename;
}

function make_thumbnail(string $src, string $dest, int $width): bool {
    if (!extension_loaded('gd')) return false;
    [$w, $h, $type] = getimagesize($src);
    if (!$w) return false;
    if ($w <= $width) { copy($src, $dest); return true; }
    $ratio = $width / $w;
    $new_h = (int) round($h * $ratio);

    $src_img = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => imagecreatefrompng($src),
        IMAGETYPE_WEBP => imagecreatefromwebp($src),
        default => null,
    };
    if (!$src_img) return false;

    $dst_img = imagecreatetruecolor($width, $new_h);
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
    }
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $width, $new_h, $w, $h);

    $ok = match ($type) {
        IMAGETYPE_JPEG => imagejpeg($dst_img, $dest, 85),
        IMAGETYPE_PNG  => imagepng($dst_img, $dest, 6),
        IMAGETYPE_WEBP => imagewebp($dst_img, $dest, 85),
        default => false,
    };
    imagedestroy($src_img);
    imagedestroy($dst_img);
    return $ok;
}
