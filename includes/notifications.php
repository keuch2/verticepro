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
     * Minimal email sender. Uses PHP mail() in development; in production
     * replace with SMTP/PHPMailer. Returns true on apparent success.
     */
    private static function sendEmail(string $to, string $name, string $subject, ?string $body, ?string $link): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $env = cfg()['env'] ?? 'development';
        $from = 'no-reply@verticepro.com.py';
        $headers = [
            'From: Vértice Pro <' . $from . '>',
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
        $msg = trim(($name ? "Hola $name,\n\n" : '') . ($body ?: '') . ($absolute_link ? "\n\nDetalles: " . $absolute_link : '') . "\n\n— Vértice Pro");

        // In development, log instead of sending — avoids depending on a local MTA.
        if ($env !== 'production') {
            $log = __DIR__ . '/../logs/mail.log';
            @file_put_contents($log,
                "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\n$msg\n---\n",
                FILE_APPEND
            );
            return true;
        }
        return @mail($to, $subject, $msg, implode("\r\n", $headers));
    }
}
