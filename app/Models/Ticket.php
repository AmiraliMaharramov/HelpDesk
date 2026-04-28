<?php
/**
 * QuickFixDesk — Ticket Model
 * Covers ticket CRUD, status workflow, AI routing, and SLA.
 */

require_once APP_PATH . '/Models/Model.php';

class Ticket extends Model
{
    protected static string $table = 'tickets';

    // ── Creation ──────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        $db   = self::db();
        $stmt = $db->prepare(
            "INSERT INTO `tickets`
             (ticket_number, client_id, type, subject, description,
              priority, category_id, assigned_to, department_id,
              service_address_id, remote_tool, remote_code,
              sla_deadline, auto_routed, status)
             VALUES
             (:ticket_number, :client_id, :type, :subject, :description,
              :priority, :category_id, :assigned_to, :department_id,
              :service_address_id, :remote_tool, :remote_code,
              :sla_deadline, :auto_routed, 'open')"
        );
        $stmt->execute([
            ':ticket_number'     => $data['ticket_number'],
            ':client_id'         => (int)$data['client_id'],
            ':type'              => $data['type'],
            ':subject'           => $data['subject'],
            ':description'       => $data['description'],
            ':priority'          => $data['priority']           ?? 'medium',
            ':category_id'       => $data['category_id']        ?? null,
            ':assigned_to'       => $data['assigned_to']        ?? null,
            ':department_id'     => $data['department_id']      ?? null,
            ':service_address_id'=> $data['service_address_id'] ?? null,
            ':remote_tool'       => $data['remote_tool']        ?? null,
            ':remote_code'       => $data['remote_code']        ?? null,
            ':sla_deadline'      => $data['sla_deadline']       ?? null,
            ':auto_routed'       => (int)($data['auto_routed']  ?? 0),
        ]);
        return (int)$db->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT t.*, u.first_name, u.last_name, u.email,
                    a.first_name AS tech_first, a.last_name AS tech_last
             FROM `tickets` t
             LEFT JOIN `users` u ON u.id = t.client_id
             LEFT JOIN `users` a ON a.id = t.assigned_to
             WHERE t.id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByNumber(string $number): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM `tickets` WHERE ticket_number=:n");
        $stmt->execute([':n' => $number]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** All tickets for a specific client, newest first. */
    public static function findByClient(int $clientId, int $limit = 50): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM `tickets` WHERE client_id=:cid ORDER BY created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':cid', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tickets not yet assigned to any technician. */
    public static function getUnassigned(): array
    {
        return self::db()
            ->query(
                "SELECT t.*, u.first_name, u.last_name
                 FROM `tickets` t
                 JOIN `users` u ON u.id = t.client_id
                 WHERE t.assigned_to IS NULL
                   AND t.status NOT IN ('closed','cancelled')
                 ORDER BY t.created_at ASC"
            )
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Active tickets assigned to a specific technician. */
    public static function findByTechnician(int $techId, int $limit = 100): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM `tickets`
             WHERE assigned_to=:tech
               AND status NOT IN ('closed','cancelled')
             ORDER BY created_at DESC LIMIT :lim"
        );
        $stmt->bindValue(':tech', $techId, PDO::PARAM_INT);
        $stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Status updates ────────────────────────────────────────────────────────

    public static function updateStatus(int $id, string $newStatus, ?int $changedBy, ?string $note = null): void
    {
        $db  = self::db();
        $old = self::findById($id);

        $closed = ($newStatus === 'closed' || $newStatus === 'cancelled')
            ? 'NOW()'
            : 'NULL';

        $db->prepare(
            "UPDATE `tickets`
             SET status=:status, closed_at={$closed}, updated_at=NOW()
             WHERE id=:id"
        )->execute([':status' => $newStatus, ':id' => $id]);

        // Record history
        $db->prepare(
            "INSERT INTO `ticket_status_history`
             (ticket_id, changed_by, old_status, new_status, note)
             VALUES (:tid, :uid, :old, :new, :note)"
        )->execute([
            ':tid'  => $id,
            ':uid'  => $changedBy,
            ':old'  => $old['status'] ?? null,
            ':new'  => $newStatus,
            ':note' => $note,
        ]);
    }

    public static function assignTo(int $id, ?int $technicianId): void
    {
        $stmt = self::db()->prepare(
            "UPDATE `tickets` SET assigned_to=:tech, updated_at=NOW() WHERE id=:id"
        );
        $stmt->execute([':tech' => $technicianId, ':id' => $id]);
    }

    /**
     * Move all active tickets of a technician to the unassigned pool.
     * Called when a staff member is deactivated.
     */
    public static function poolTicketsOf(int $technicianId): int
    {
        $stmt = self::db()->prepare(
            "UPDATE `tickets`
             SET assigned_to=NULL, updated_at=NOW()
             WHERE assigned_to=:tech
               AND status NOT IN ('closed','cancelled','delivered')"
        );
        $stmt->execute([':tech' => $technicianId]);
        return $stmt->rowCount();
    }

    // ── Ticket numbering ──────────────────────────────────────────────────────

    public static function generateNumber(): string
    {
        $year  = date('Y');
        $stmt  = self::db()->prepare(
            "SELECT COUNT(*) FROM `tickets`
             WHERE YEAR(created_at) = :year"
        );
        $stmt->execute([':year' => $year]);
        $seq   = (int)$stmt->fetchColumn() + 1;
        return 'TKT-' . $year . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    // ── SLA helper ────────────────────────────────────────────────────────────

    /**
     * Calculate SLA deadline based on ticket priority.
     * Returns a MySQL-formatted datetime string.
     */
    public static function calcSlaDeadline(string $priority): string
    {
        $hours = match ($priority) {
            'urgent' => 1,
            'high'   => 4,
            'medium' => 8,
            default  => 24,
        };
        return date('Y-m-d H:i:s', time() + ($hours * 3600));
    }

    // ── Statistics ────────────────────────────────────────────────────────────

    public static function countByStatus(): array
    {
        $rows = self::db()
            ->query("SELECT status, COUNT(*) AS cnt FROM `tickets` GROUP BY status")
            ->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['cnt'];
        }
        return $map;
    }

    public static function slaViolations(): int
    {
        $stmt = self::db()->query(
            "SELECT COUNT(*) FROM `tickets`
             WHERE sla_deadline IS NOT NULL
               AND sla_deadline < NOW()
               AND status NOT IN ('closed','cancelled')"
        );
        return (int)$stmt->fetchColumn();
    }
}
