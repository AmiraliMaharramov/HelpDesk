<?php
/**
 * QuickFixDesk — SseController
 * Server-Sent Events stream for real-time notifications.
 *
 * Clients connect to GET /api/sse/notifications and receive
 * events whenever a new unread web notification exists for
 * the authenticated user.
 *
 * The connection runs for up to 25 seconds (safe for shared
 * hosting) then closes; the browser EventSource API reconnects
 * automatically using the Last-Event-ID header.
 */

require_once APP_PATH . '/Controllers/Controller.php';

class SseController extends Controller
{
    /**
     * GET /api/sse/notifications
     */
    public function stream(): void
    {
        $this->requireAuth();

        // Disable PHP output buffering & time limits
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', 'off');
        set_time_limit(30);
        ignore_user_abort(false);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');    // disable nginx buffering
        header('Connection: keep-alive');

        if (ob_get_level()) {
            ob_end_flush();
        }

        $userId      = (int)$_SESSION['user_id'];
        $lastEventId = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? $_GET['lastId'] ?? 0);
        $db          = db();
        $deadline    = time() + 25;   // max 25 s per connection

        while (!connection_aborted() && time() < $deadline) {
            // Fetch new unread notifications
            $stmt = $db->prepare(
                "SELECT id, type, title, body, data, created_at
                 FROM notifications
                 WHERE user_id = :uid
                   AND id      > :last
                   AND read_at IS NULL
                   AND channel = 'web'
                 ORDER BY id ASC LIMIT 10"
            );
            $stmt->execute([':uid' => $userId, ':last' => $lastEventId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $lastEventId = (int)$row['id'];
                $payload = [
                    'id'    => $lastEventId,
                    'type'  => $row['type'],
                    'title' => $row['title'],
                    'body'  => $row['body'],
                    'data'  => $row['data'] ? json_decode($row['data'], true) : null,
                    'time'  => $row['created_at'],
                ];
                echo "id: {$lastEventId}\n";
                echo "event: notification\n";
                echo "data: " . json_encode($payload) . "\n\n";

                // Mark as sent (not read — reading is separate)
                $db->prepare("UPDATE notifications SET sent_at=NOW() WHERE id=:id")
                   ->execute([':id' => $lastEventId]);
            }

            // Heartbeat so proxies don't close the connection
            echo ": ping\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(3);
        }

        // Tell the client the retry interval (2 s) so it reconnects quickly
        echo "retry: 2000\n\n";
        flush();
        exit;
    }
}
