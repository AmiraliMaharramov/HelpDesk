<?php
/**
 * QuickFixDesk — QrController
 * Public device-service QR-code tracking page.
 */

require_once APP_PATH . '/Controllers/Controller.php';

class QrController extends Controller
{
    /**
     * GET /track/:code
     *
     * Public, no auth required. Renders a read-only repair-status
     * page for the device identified by the QR token.
     */
    public function track(string $code): void
    {
        $code = clean($code);

        $stmt = db()->prepare(
            "SELECT ds.*,
                    t.ticket_number, t.subject, t.status AS ticket_status,
                    t.created_at    AS ticket_created,
                    u.first_name    AS client_first,
                    u.last_name     AS client_last
             FROM devices_service ds
             JOIN tickets t  ON t.id  = ds.ticket_id
             JOIN users   u  ON u.id  = t.client_id
             WHERE ds.qr_code = :code
             LIMIT 1"
        );
        $stmt->execute([':code' => $code]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            http_response_code(404);
            $this->view('public/not-found', [], 'plain');
            return;
        }

        // Fetch status history for the ticket
        $hist = db()->prepare(
            "SELECT h.new_status, h.note, h.created_at,
                    u.first_name, u.last_name
             FROM ticket_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.ticket_id = :tid
             ORDER BY h.created_at ASC"
        );
        $hist->execute([':tid' => $device['ticket_id']]);
        $history = $hist->fetchAll(PDO::FETCH_ASSOC);

        audit('qr_scan', 'devices_service', 'device_service', (int)$device['id']);

        $this->view('public/device-tracking', [
            'pageTitle' => __t('track_title') . ' — ' . e($device['ticket_number']),
            'device'    => $device,
            'history'   => $history,
        ], 'plain');
    }
}
