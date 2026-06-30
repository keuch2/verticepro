<?php
/**
 * Diagnóstico de producción — Vértice Pro
 * --------------------------------------------------
 * Sube este archivo y ábrelo en el navegador (logueado como admin):
 *     https://TU-DOMINIO/admin/diagnostico.php
 *
 * Revisa: PHP/extensiones, permisos de escritura de carpetas de subida,
 * límites de upload, conexión a BD y columnas/tablas que el código nuevo
 * necesita. Te dice exactamente qué falta en producción.
 *
 * BORRA este archivo cuando termines (expone info del servidor).
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

header('Content-Type: text/html; charset=utf-8');

function row(string $label, bool $ok, string $detail = ''): void {
    $color = $ok ? '#2e7d32' : '#c62828';
    $icon  = $ok ? '&#10003;' : '&#10007;';
    echo '<tr>'
        . '<td style="padding:6px 12px;border-bottom:1px solid #eee;">' . htmlspecialchars($label) . '</td>'
        . '<td style="padding:6px 12px;border-bottom:1px solid #eee;color:' . $color . ';font-weight:700;text-align:center;">' . $icon . '</td>'
        . '<td style="padding:6px 12px;border-bottom:1px solid #eee;color:#555;font-family:monospace;font-size:13px;">' . htmlspecialchars($detail) . '</td>'
        . '</tr>';
}
function section_title(string $t): void {
    echo '<tr><td colspan="3" style="padding:16px 12px 4px;font-weight:800;font-size:15px;background:#f5f5f5;border-top:2px solid #ddd;">' . htmlspecialchars($t) . '</td></tr>';
}

$cfg = cfg();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Diagnóstico — Vértice Pro</title></head>
<body style="font-family:system-ui,Arial,sans-serif;max-width:1000px;margin:30px auto;padding:0 16px;color:#1a1a1a;">
<h1 style="margin-bottom:4px;">Diagnóstico de producción</h1>
<p style="color:#888;margin-top:0;">Borra <code>admin/diagnostico.php</code> cuando termines.</p>
<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;">
<thead><tr style="background:#1a1a1a;color:#fff;"><th style="padding:8px 12px;text-align:left;">Comprobación</th><th style="padding:8px 12px;">OK</th><th style="padding:8px 12px;text-align:left;">Detalle</th></tr></thead>
<tbody>

<?php
// ───────────────────────── Entorno / PHP ─────────────────────────
section_title('Entorno PHP');
row('Versión de PHP', version_compare(PHP_VERSION, '8.0', '>='), PHP_VERSION);
row('Entorno (config env)', true, $cfg['env'] ?? '(no definido)');
row('Extensión GD (miniaturas)', extension_loaded('gd'), extension_loaded('gd') ? (gd_info()['GD Version'] ?? 'ok') : 'FALTA — instalar php-gd');
row('GD soporta JPEG', function_exists('imagecreatefromjpeg') && (gd_info()['JPEG Support'] ?? false), '');
row('GD soporta PNG', function_exists('imagecreatefrompng') && (gd_info()['PNG Support'] ?? false), '');
row('GD soporta WEBP', function_exists('imagecreatefromwebp') && (gd_info()['WebP Support'] ?? false), '');
row('Extensión fileinfo (MIME)', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'ok' : 'FALTA — los uploads serán rechazados siempre');
row('Extensión pdo_mysql', extension_loaded('pdo_mysql'), '');

// ───────────────────────── Límites de subida ─────────────────────────
section_title('Límites de subida (php.ini)');
$fu = ini_get('file_uploads');
row('file_uploads activado', (bool)$fu, $fu ? 'On' : 'Off — NINGÚN upload funcionará');
$umax = ini_get('upload_max_filesize');
$pmax = ini_get('post_max_size');
row('upload_max_filesize', true, $umax);
row('post_max_size (debe ser ≥ upload_max_filesize)', (return_bytes($pmax) >= return_bytes($umax)), $pmax);
row('memory_limit', true, ini_get('memory_limit'));
$tmp = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
row('Carpeta temporal de subida escribible', is_writable($tmp), $tmp);

// ───────────────────────── Carpetas de escritura ─────────────────────────
section_title('Permisos de escritura (causa #1 de errores al subir)');
$root = dirname(__DIR__);
$img_root = $cfg['img_path'];
$paths = [
    'img/ (raíz de imágenes)'      => $img_root,
    'img/articles'                 => $img_root . '/articles',
    'img/articles/inline (TinyMCE)'=> $img_root . '/articles/inline',
    'img/profiles (fotos perfil)'  => $img_root . '/profiles',
    'img/companies (logos)'        => $img_root . '/companies',
    'img/publications'             => $img_root . '/publications',
    'uploads/ (documentos)'        => $root . '/uploads',
    'uploads/contributions'        => $root . '/uploads/contributions',
    'logs/ (mail.log)'             => $root . '/logs',
];
foreach ($paths as $label => $p) {
    $exists = is_dir($p);
    $writable = $exists ? is_writable($p) : is_writable(dirname($p));
    $detail = $p;
    if ($exists) {
        $perms = substr(sprintf('%o', fileperms($p)), -4);
        $owner = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($p))['name'] ?? fileowner($p)) : fileowner($p);
        $detail = "perms=$perms owner=$owner";
    } else {
        $detail = 'NO EXISTE — el padre ' . (is_writable(dirname($p)) ? 'es escribible (se creará)' : 'NO es escribible (mkdir fallará)');
    }
    row($label, $writable, $detail);
}
row('Usuario del proceso web', true, function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '?') : (getenv('USER') ?: get_current_user()));

// ───────────────────────── img_path resuelto ─────────────────────────
section_title('Rutas configuradas');
row('img_path (filesystem) existe', is_dir($img_root), $img_root);
row('img_url (web)', true, $cfg['img_url'] ?? '');
row('base_path', true, "'" . ($cfg['base_path'] ?? '') . "'  (vacío = sitio en raíz del dominio)");
row('base_url', true, $cfg['base_url'] ?? '');

// ───────────────────────── Base de datos ─────────────────────────
section_title('Base de datos — conexión');
$db_ok = false;
try {
    DB::pdo();
    $db_ok = true;
    row('Conexión a MySQL', true, $cfg['db']['name'] . '@' . $cfg['db']['host']);
} catch (\Throwable $e) {
    row('Conexión a MySQL', false, $e->getMessage());
}

if ($db_ok) {
    // Tablas que el código nuevo necesita
    section_title('Base de datos — tablas requeridas por el código nuevo');
    $need_tables = [
        'users', 'professionals', 'companies', 'job_offers', 'services',
        'company_services'        => 'migración 2026-06-10',
        'company_service_links'   => 'migración 2026-06-10',
        'company_sector_links'    => 'migración 2026-05-27 (multi-sector)',
        'professional_type_links' => 'migración 2026-05-27 (multi-tipo)',
        'professional_types'      => 'minuta lanzamiento',
        'application_messages'    => 'migración 2026-06-10 (postulaciones)',
    ];
    $existing = [];
    foreach (DB::all('SHOW TABLES') as $r) { $existing[] = array_values($r)[0]; }
    foreach ($need_tables as $k => $v) {
        $table = is_int($k) ? $v : $k;
        $note  = is_int($k) ? '' : $v;
        $ok = in_array($table, $existing, true);
        row("tabla: $table", $ok, $ok ? 'ok' : "FALTA — aplicar $note");
    }

    // Columnas que los INSERT de registro necesitan
    section_title('Base de datos — columnas requeridas (causa de pantalla blanca)');
    $need_cols = [
        'companies'     => ['phone', 'visibility_email', 'visibility_phone', 'visibility_website', 'notifications_opt_in', 'user_id', 'logo_image', 'size'],
        'professionals' => ['avatar_image', 'type_id', 'phone', 'visibility_email', 'visibility_phone', 'visibility_linkedin', 'visibility_website', 'notifications_opt_in', 'user_id'],
        'job_offers'    => ['flyer_image'],
        'services'      => ['flyer_image'],
        'job_interests' => ['status', 'status_updated_at'],
        'articles'      => ['body', 'hero_image', 'thumb_image', 'section_slug', 'discipline_id'],
    ];
    foreach ($need_cols as $table => $cols) {
        if (!in_array($table, $existing, true)) { row("$table.*", false, 'la tabla no existe'); continue; }
        $present = [];
        foreach (DB::all("SHOW COLUMNS FROM `$table`") as $c) { $present[] = $c['Field']; }
        foreach ($cols as $col) {
            $ok = in_array($col, $present, true);
            row("$table.$col", $ok, $ok ? '' : 'FALTA — el INSERT/SELECT fallará con esta columna');
        }
    }
}
?>
</tbody></table>

<h2 style="margin-top:30px;">Prueba real de escritura de imagen</h2>
<?php
// Intento real de crear/redimensionar una imagen en img/profiles
$test_dir = $img_root . '/profiles';
$test_msg = [];
$ok_write = false;
try {
    if (!is_dir($test_dir)) @mkdir($test_dir, 0775, true);
    if (extension_loaded('gd')) {
        $im = imagecreatetruecolor(20, 20);
        $test_file = $test_dir . '/_diag_test_' . bin2hex(random_bytes(2)) . '.png';
        $ok_write = @imagepng($im, $test_file);
        imagedestroy($im);
        if ($ok_write) { $test_msg[] = "Escritura + GD OK → $test_file"; @unlink($test_file); }
        else { $test_msg[] = "FALLÓ imagepng() en $test_dir — revisar permisos/propietario."; }
    } else {
        $test_msg[] = 'GD no está instalado: make_thumbnail() devuelve false y los uploads de imagen fallan en el redimensionado.';
    }
} catch (\Throwable $e) {
    $test_msg[] = 'Excepción: ' . $e->getMessage();
}
echo '<p style="font-family:monospace;background:#f5f5f5;padding:12px;border-radius:6px;color:' . ($ok_write ? '#2e7d32' : '#c62828') . ';">' . htmlspecialchars(implode("\n", $test_msg)) . '</p>';

function return_bytes($val): int {
    $val = trim((string)$val);
    if ($val === '') return 0;
    $last = strtolower($val[strlen($val) - 1]);
    $num = (int)$val;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}
?>
<p style="margin-top:24px;color:#c62828;font-weight:700;">⚠ Recuerda borrar este archivo (admin/diagnostico.php) al terminar.</p>
</body></html>
