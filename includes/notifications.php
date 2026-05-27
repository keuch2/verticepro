<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Notify — central helper for creating notifications (in-app + email).
 *
 * Patterns:
 *   - Authenticated user with user_id  → in-app row + (optional) email
 *   - Public registrant (only email)   → email only
 *
 * Notification types in use:
 *   - 'profile_approved'      profesional o empresa aprobada por admin
 *   - 'new_interest'          un usuario marcó "me interesa" en una oferta
 *   - 'new_offer'             (futuro) ofertas nuevas para profesionales que siguen una disciplina
 */
class Notify {
    public static function create(int $user_id, string $type, string $title, ?string $body = null, ?string $link = null, ?string $email_to = null): int {
        $id = DB::insert('notifications', [
            'user_id' => $user_id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'link'    => $link,
        ]);

        // Send email if user opted in (and we have an address).
        $user = DB::one('SELECT email, name, notifications_opt_in FROM users WHERE id = ?', [$user_id]);
        if ($user && (int)$user['notifications_opt_in'] === 1) {
            self::sendEmail($user['email'], $user['name'], $title, $body, $link);
            DB::update('notifications', ['email_sent_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        } elseif ($email_to) {
            // Fallback: send to an explicit address (used for pending registrants without user account).
            self::sendEmail($email_to, '', $title, $body, $link);
            DB::update('notifications', ['email_sent_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        }
        return $id;
    }

    /**
     * Send-only path — used when there is no user_id yet (public registrant).
     * Respects $opt_in (1=send, 0=skip).
     */
    public static function emailOnly(string $email, string $name, int $opt_in, string $title, ?string $body = null, ?string $link = null): bool {
        if (!$opt_in) return false;
        return self::sendEmail($email, $name, $title, $body, $link);
    }

    public static function unreadCount(int $user_id): int {
        $r = DB::one('SELECT COUNT(*) n FROM notifications WHERE user_id = ? AND read_at IS NULL', [$user_id]);
        return (int)($r['n'] ?? 0);
    }

    public static function listFor(int $user_id, int $limit = 50): array {
        return DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int)$limit, [$user_id]);
    }

    public static function markRead(int $id, int $user_id): void {
        DB::run('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL', [$id, $user_id]);
    }

    public static function markAllRead(int $user_id): void {
        DB::run('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$user_id]);
    }

    /**
     * Email sender. Si SMTP está habilitado en settings, lo usa.
     * Si no, cae en mail() del sistema (production) o log a archivo (development).
     */
    private static function sendEmail(string $to, string $name, string $subject, ?string $body, ?string $link): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $env = cfg()['env'] ?? 'development';

        $from_email = function_exists('setting') ? (setting('smtp.from_email') ?: 'no-reply@verticepro.com.py') : 'no-reply@verticepro.com.py';
        $from_name  = function_exists('setting') ? (setting('smtp.from_name')  ?: 'Vértice Pro')                : 'Vértice Pro';
        $smtp_on    = function_exists('setting') && setting('smtp.enabled') === '1';
        $from = $from_email;
        $headers = [
            'From: ' . self::encodeHeader($from_name) . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=utf-8',
        ];
        $absolute_link = '';
        if ($link) {
            if (preg_match('#^https?://#i', $link)) {
                $absolute_link = $link;
            } else {
                // $link may already include base_path (e.g. from u()). Build the host portion
                // by stripping base_path from base_url to avoid duplicating it.
                $base_url = rtrim(cfg()['base_url'] ?? '', '/');
                $base_path = rtrim(cfg()['base_path'] ?? '', '/');
                if ($base_path && str_ends_with($base_url, $base_path)) {
                    $base_url = substr($base_url, 0, -strlen($base_path));
                }
                $absolute_link = rtrim($base_url, '/') . '/' . ltrim($link, '/');
            }
        }
        $msg = trim(($name ? "Hola $name,\n\n" : '') . ($body ?: '') . ($absolute_link ? "\n\nDetalles: " . $absolute_link : '') . "\n\n— " . $from_name);

        // In development, log instead of sending — avoids depending on a local MTA.
        if ($env !== 'production') {
            $log = __DIR__ . '/../logs/mail.log';
            @file_put_contents($log,
                "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject | SMTP=" . ($smtp_on ? 'on' : 'off') . "\n$msg\n---\n",
                FILE_APPEND
            );
            return true;
        }

        // Production: SMTP de settings o mail() del sistema
        if ($smtp_on) {
            return self::sendSmtp($to, $from_email, $from_name, $subject, $msg);
        }
        return @mail($to, self::encodeHeader($subject), $msg, implode("\r\n", $headers));
    }

    private static function encodeHeader(string $s): string {
        return preg_match('/[^\x20-\x7e]/', $s)
            ? '=?UTF-8?B?' . base64_encode($s) . '?='
            : $s;
    }

    /**
     * Cliente SMTP minimalista con STARTTLS / SSL implícito / sin TLS.
     */
    private static function sendSmtp(string $to, string $from_email, string $from_name, string $subject, string $body): bool {
        $host = setting('smtp.host');
        $port = (int)(setting('smtp.port') ?: 587);
        $user = setting('smtp.user');
        $pass = setting('smtp.pass');
        $enc  = setting('smtp.encryption', 'tls');
        if (!$host) { self::smtpLog('no host configured'); return false; }

        $transport = ($enc === 'ssl') ? "ssl://$host" : $host;
        $errno = 0; $errstr = '';
        $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$fp) { self::smtpLog("connect fail: $errstr ($errno)"); return false; }
        stream_set_timeout($fp, 15);

        $readLine = function() use ($fp) {
            $out = '';
            while (!feof($fp)) {
                $line = fgets($fp, 1024);
                if ($line === false) break;
                $out .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $out;
        };
        $expect = function(string $code) use ($readLine) {
            $r = $readLine();
            if (substr($r, 0, 3) !== $code) {
                Notify::smtpLog("expected $code got: " . trim($r));
                return false;
            }
            return true;
        };
        $send = function(string $cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

        if (!$expect('220')) { fclose($fp); return false; }
        $hostname = $_SERVER['HTTP_HOST'] ?? 'verticepro.com.py';
        $send('EHLO ' . $hostname);
        if (!$expect('250')) { fclose($fp); return false; }

        if ($enc === 'tls') {
            $send('STARTTLS');
            if (!$expect('220')) { fclose($fp); return false; }
            if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                self::smtpLog('STARTTLS handshake failed'); fclose($fp); return false;
            }
            $send('EHLO ' . $hostname);
            if (!$expect('250')) { fclose($fp); return false; }
        }

        if ($user) {
            $send('AUTH LOGIN');
            if (!$expect('334')) { fclose($fp); return false; }
            $send(base64_encode($user));
            if (!$expect('334')) { fclose($fp); return false; }
            $send(base64_encode((string)$pass));
            if (!$expect('235')) { fclose($fp); return false; }
        }

        $send("MAIL FROM:<$from_email>");
        if (!$expect('250')) { fclose($fp); return false; }
        $send("RCPT TO:<$to>");
        $r = $readLine();
        if (substr($r, 0, 3) !== '250' && substr($r, 0, 3) !== '251') {
            self::smtpLog('RCPT TO failed: ' . trim($r)); fclose($fp); return false;
        }
        $send('DATA');
        if (!$expect('354')) { fclose($fp); return false; }

        $headers = "From: " . self::encodeHeader($from_name) . " <$from_email>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: " . self::encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";

        $body_smtp = preg_replace('/^\./m', '..', $body);
        fwrite($fp, $headers . $body_smtp . "\r\n.\r\n");
        if (!$expect('250')) { fclose($fp); return false; }
        $send('QUIT');
        fclose($fp);
        return true;
    }

    public static function smtpLog(string $msg): void {
        $log = __DIR__ . '/../logs/smtp.log';
        @file_put_contents($log, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
    }
}
