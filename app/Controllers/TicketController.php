<?php
/**
 * QuickFixDesk — TicketController
 * Multi-step ticket creation (On-site / Remote / Device)
 * with AI routing and SLA assignment.
 */

require_once APP_PATH . '/Controllers/Controller.php';
require_once APP_PATH . '/Models/Ticket.php';
require_once APP_PATH . '/Models/Faq.php';
require_once APP_PATH . '/Models/Department.php';
require_once APP_PATH . '/Helpers/Csrf.php';

class TicketController extends Controller
{
    // ── Multi-step creation form ──────────────────────────────────────────────

    /**
     * GET /tickets/new
     */
    public function createForm(): void
    {
        $this->requireAuth('individual', 'corporate');

        $addresses = db()->prepare(
            "SELECT * FROM addresses WHERE user_id=:uid ORDER BY is_default DESC"
        );
        $addresses->execute([':uid' => $_SESSION['user_id']]);

        $categories = db()->query(
            "SELECT * FROM ticket_categories WHERE is_active=1 ORDER BY sort_order ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $this->view('dashboard/tickets/create', [
            'pageTitle'  => __t('ticket_new'),
            'addresses'  => $addresses->fetchAll(PDO::FETCH_ASSOC),
            'categories' => $categories,
            'errors'     => [],
            'old'        => [],
        ], 'dashboard');
    }

    /**
     * POST /tickets/new
     * Validates all steps, runs AI routing, creates ticket + device record.
     */
    public function create(): void
    {
        $this->requireAuth('individual', 'corporate');
        Csrf::verify();

        $type        = clean($_POST['type']        ?? '');
        $subject     = clean($_POST['subject']     ?? '');
        $description = clean($_POST['description'] ?? '');
        $priority    = clean($_POST['priority']    ?? 'medium');

        $errors = [];

        // ── Step 1 validation ─────────────────────────────────────────────────
        if (!in_array($type, ['onsite', 'remote', 'device'])) {
            $errors['type'] = __t('validation_required');
        }
        if (empty($subject)) {
            $errors['subject'] = __t('validation_required');
        }
        if (empty($description)) {
            $errors['description'] = __t('validation_required');
        }

        // ── Step 2 validation (type-specific) ─────────────────────────────────
        $addressId   = null;
        $remoteTool  = null;
        $remoteCode  = null;
        $deviceData  = [];

        switch ($type) {
            case 'onsite':
                $addressId = (int)($_POST['address_id'] ?? 0);
                if ($addressId <= 0) {
                    $errors['address_id'] = __t('validation_required');
                }
                break;

            case 'remote':
                $remoteTool = clean($_POST['remote_tool'] ?? '');
                $remoteCode = clean($_POST['remote_code'] ?? '');
                if (empty($remoteTool)) {
                    $errors['remote_tool'] = __t('validation_required');
                }
                if (empty($remoteCode)) {
                    $errors['remote_code'] = __t('validation_required');
                }
                break;

            case 'device':
                $deviceData = [
                    'device_type'     => clean($_POST['device_type']     ?? ''),
                    'brand'           => clean($_POST['brand']           ?? ''),
                    'model'           => clean($_POST['model_name']      ?? ''),
                    'serial_number'   => clean($_POST['serial_number']   ?? ''),
                    'condition_notes' => clean($_POST['condition_notes'] ?? ''),
                    'accessories'     => clean($_POST['accessories']     ?? ''),
                    'warranty_days'   => (int)($_POST['warranty_days']   ?? 0),
                ];
                if (empty($deviceData['device_type'])) {
                    $errors['device_type'] = __t('validation_required');
                }
                if (empty($deviceData['serial_number'])) {
                    $errors['serial_number'] = __t('validation_required');
                }
                break;
        }

        if (!empty($errors)) {
            $addresses = db()->prepare(
                "SELECT * FROM addresses WHERE user_id=:uid ORDER BY is_default DESC"
            );
            $addresses->execute([':uid' => $_SESSION['user_id']]);

            $this->view('dashboard/tickets/create', [
                'pageTitle'  => __t('ticket_new'),
                'addresses'  => $addresses->fetchAll(PDO::FETCH_ASSOC),
                'categories' => db()->query("SELECT * FROM ticket_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC),
                'errors'     => $errors,
                'old'        => $_POST,
            ], 'dashboard');
            return;
        }

        // ── AI Routing ────────────────────────────────────────────────────────
        $routing       = $this->classify($subject . ' ' . $description, $type);
        $deptSlug      = $routing['department'];
        $dept          = Department::findBySlug($deptSlug);
        $deptId        = $dept['id'] ?? null;
        $assignedTo    = $this->findAvailableTechnician($deptId);

        // ── SLA deadline ──────────────────────────────────────────────────────
        $slaDeadline   = Ticket::calcSlaDeadline($priority);

        // ── Create ticket ─────────────────────────────────────────────────────
        $ticketId = Ticket::create([
            'ticket_number'      => Ticket::generateNumber(),
            'client_id'          => $_SESSION['user_id'],
            'type'               => $type,
            'subject'            => $subject,
            'description'        => $description,
            'priority'           => $priority,
            'category_id'        => ($catId = (int)($_POST['category_id'] ?? 0)) > 0 ? $catId : null,
            'assigned_to'        => $assignedTo,
            'department_id'      => $deptId,
            'service_address_id' => $addressId,
            'remote_tool'        => $remoteTool,
            'remote_code'        => $remoteCode,
            'sla_deadline'       => $slaDeadline,
            'auto_routed'        => 1,
        ]);

        // ── Device service record ─────────────────────────────────────────────
        if ($type === 'device' && $ticketId) {
            $qrCode = bin2hex(random_bytes(16));
            db()->prepare(
                "INSERT INTO devices_service
                 (ticket_id, qr_code, device_type, brand, model, serial_number,
                  condition_notes, accessories, warranty_days)
                 VALUES
                 (:tid, :qr, :dt, :brand, :model, :serial,
                  :cond, :acc, :wd)"
            )->execute([
                ':tid'    => $ticketId,
                ':qr'     => $qrCode,
                ':dt'     => $deviceData['device_type'],
                ':brand'  => $deviceData['brand'],
                ':model'  => $deviceData['model'],
                ':serial' => $deviceData['serial_number'],
                ':cond'   => $deviceData['condition_notes'],
                ':acc'    => $deviceData['accessories'],
                ':wd'     => $deviceData['warranty_days'],
            ]);
        }

        // ── Notify assigned technician ────────────────────────────────────────
        if ($assignedTo) {
            notify(
                $assignedTo,
                'new_ticket',
                __t('ticket_new') . ': ' . $subject,
                __t('ticket_priority') . ': ' . $priority,
                ['ticket_id' => $ticketId, 'type' => $type]
            );
        }

        // ── Notify all techs in that dept if unassigned ───────────────────────
        if (!$assignedTo && $deptId) {
            $techStmt = db()->prepare(
                "SELECT id FROM users WHERE department_id=:did AND role_id=2 AND is_active=1"
            );
            $techStmt->execute([':did' => $deptId]);
            foreach ($techStmt->fetchAll(PDO::FETCH_COLUMN) as $techId) {
                notify((int)$techId, 'unassigned_ticket', __t('ticket_new') . ' (Pool): ' . $subject, null, ['ticket_id' => $ticketId]);
            }
        }

        // ── Audit ─────────────────────────────────────────────────────────────
        audit('ticket_created', 'tickets', 'ticket', $ticketId, null, [
            'type'     => $type,
            'priority' => $priority,
            'dept'     => $deptSlug,
        ]);

        $this->flash('success', __t('msg_success'));
        $this->redirect('/dashboard/tickets/' . $ticketId);
    }

    // ── AI Routing classifier ─────────────────────────────────────────────────

    /**
     * Keyword-based classifier that maps a ticket's text to a department slug.
     *
     * @return array{department: string, confidence: string}
     */
    private function classify(string $text, string $ticketType): array
    {
        // Type hint gives immediate confidence
        if ($ticketType === 'device') {
            return ['department' => 'field', 'confidence' => 'type'];
        }
        if ($ticketType === 'remote') {
            return ['department' => 'technical', 'confidence' => 'type'];
        }

        $text   = mb_strtolower($text);
        $scores = ['field' => 0, 'technical' => 0, 'accounting' => 0, 'sales' => 0];

        $map = [
            'field'      => ['office', 'cable', 'hardware', 'physical', 'on-site', 'onsite',
                             'visit', 'printer', 'wiring', 'setup', 'install', 'network',
                             'server', 'cable', 'router', 'switch', 'rack', 'broken', 'screen',
                             'battery', 'dropped', 'cracked', 'power'],
            'technical'  => ['login', 'software', 'password', 'error', 'crash', 'slow',
                             'virus', 'malware', 'update', 'windows', 'mac', 'browser',
                             'email', 'outlook', 'vpn', 'cloud', 'database', 'api',
                             'ssl', 'certificate', 'domain', 'dns', 'hosting'],
            'accounting' => ['invoice', 'billing', 'payment', 'refund', 'vat', 'tax',
                             'overdue', 'quote', 'receipt', 'charge', 'subscription'],
            'sales'      => ['pricing', 'plan', 'upgrade', 'demo', 'trial', 'purchase',
                             'discount', 'contract', 'proposal', 'renewal'],
        ];

        foreach ($map as $dept => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $scores[$dept]++;
                }
            }
        }

        arsort($scores);
        $top = array_key_first($scores);

        return [
            'department' => ($scores[$top] > 0) ? $top : 'technical',
            'confidence' => $scores[$top] > 2 ? 'high' : ($scores[$top] > 0 ? 'medium' : 'low'),
        ];
    }

    /**
     * Find the least-loaded active technician in a department.
     * Returns user ID or null if none found.
     */
    private function findAvailableTechnician(?int $deptId): ?int
    {
        if (!$deptId) {
            return null;
        }

        // Respect working hours
        $now  = date('H:i:s');
        $stmt = db()->prepare(
            "SELECT u.id, COUNT(t.id) AS load
             FROM users u
             LEFT JOIN tickets t
                ON t.assigned_to = u.id
               AND t.status NOT IN ('closed','cancelled','delivered')
             WHERE u.role_id      = 2
               AND u.is_active    = 1
               AND u.department_id = :did
               AND :now BETWEEN u.work_start AND u.work_end
             GROUP BY u.id
             ORDER BY load ASC
             LIMIT 1"
        );
        $stmt->execute([':did' => $deptId, ':now' => $now]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }
}
