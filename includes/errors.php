<?php
/**
 * Manejador global de errores y excepciones.
 *
 * - En 'development': muestra el error completo en pantalla (display_errors on).
 * - En 'production' : NO muestra detalles al visitante; registra el error real en
 *   logs/php-errors.log y muestra una página amable. Esto elimina las "pantallas
 *   blancas" mudas: el motivo real queda siempre en el log.
 *
 * Incluido temprano por includes/bootstrap.php.
 */

(function () {
    $cfg = cfg();
    $env = $cfg['env'] ?? 'production';
    $is_dev = ($env === 'development');

    $log_dir = dirname(__DIR__) . '/logs';
    $log_file = $log_dir . '/php-errors.log';
    // Best-effort: asegurar carpeta de logs.
    if (!is_dir($log_dir)) { @mkdir($log_dir, 0775, true); }

    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    if (is_writable($log_dir) || (!file_exists($log_file) && is_writable($log_dir))) {
        ini_set('error_log', $log_file);
    }
    // En dev mostramos; en prod nunca mostramos detalles al visitante.
    ini_set('display_errors', $is_dev ? '1' : '0');

    /** Registra un evento en el log con timestamp y contexto de petición. */
    $log_event = function (string $msg) use ($log_file) {
        $when = date('Y-m-d H:i:s');
        $uri  = $_SERVER['REQUEST_URI'] ?? 'cli';
        $method = $_SERVER['REQUEST_METHOD'] ?? '-';
        $line = "[$when] [$method $uri] $msg" . PHP_EOL;
        @error_log($line, 3, $log_file);
    };

    /** Página amable para el visitante (sólo en producción). */
    $render_friendly = function (int $code = 500) {
        if (headers_sent()) return;
        http_response_code($code);
        // Evitar cachear una respuesta de error.
        header('Cache-Control: no-store');
        // Si la petición espera JSON (p.ej. upload_image.php), responder JSON.
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (stripos($accept, 'application/json') !== false || strcasecmp($xrw, 'XMLHttpRequest') === 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Ocurrió un error en el servidor. Inténtalo de nuevo.']);
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Algo salió mal — Vértice Pro</title></head>'
            . '<body style="font-family:system-ui,Arial,sans-serif;max-width:560px;margin:80px auto;padding:0 20px;color:#1A1A1A;text-align:center;">'
            . '<h1 style="font-size:22px;margin-bottom:8px;">Algo salió mal</h1>'
            . '<p style="color:#54636F;line-height:1.5;">Tuvimos un problema procesando tu solicitud. '
            . 'El equipo ya quedó notificado. Por favor vuelve a intentarlo en unos minutos.</p>'
            . '<p style="margin-top:24px;"><a href="/" style="color:#F58220;font-weight:700;text-decoration:none;">Volver al inicio</a></p>'
            . '</body></html>';
    };

    // Excepciones no capturadas.
    set_exception_handler(function (\Throwable $e) use ($is_dev, $log_event, $render_friendly) {
        $log_event('UNCAUGHT ' . get_class($e) . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($is_dev) {
            // Dejar que el visitante vea el detalle en desarrollo.
            http_response_code(500);
            echo '<pre style="white-space:pre-wrap;color:#b00;padding:16px;font-family:monospace;">'
                . htmlspecialchars((string)$e) . '</pre>';
        } else {
            $render_friendly(500);
        }
    });

    // Errores fatales (parse/E_ERROR) vía shutdown.
    register_shutdown_function(function () use ($is_dev, $log_event, $render_friendly) {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $log_event('FATAL ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
            if (!$is_dev) {
                // En prod, descartar cualquier salida parcial y mostrar página amable.
                if (!headers_sent()) { @ob_end_clean(); }
                $render_friendly(500);
            }
        }
    });
})();
