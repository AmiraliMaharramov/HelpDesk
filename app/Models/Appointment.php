<?php
/**
 * QuickFixDesk — Appointment Model
 * Handles scheduling with conflict prevention.
 */

require_once APP_PATH . '/Models/Model.php';

class Appointment extends Model
{
    protected static string $table = 'appointments';

    public static function create(array $data): int
    {
        $db   = self::db();
        $stmt = $db->prepare(
            "INSERT INTO `appointments`
             (ticket_id, client_id, technician_id, department_id, type,
              title, notes, start_at, end_at, address_id, status)
             VALUES
             (:ticket_id, :client_id, :technician_id, :department_id, :type,
              :title, :notes, :start_at, :end_at, :address_id, 'scheduled')"
        );
        $stmt->execute([
            ':ticket_id'     => $data['ticket_id']     ?? null,
            ':client_id'     => (int)$data['client_id'],
            ':technician_id' => $data['technician_id'] ?? null,
            ':department_id' => $data['department_id'] ?? null,
            ':type'          => $data['type']          ?? 'onsite',
            ':title'         => $data['title'],
            ':notes'         => $data['notes']         ?? null,
            ':start_at'      => $data['start_at'],
            ':end_at'        => $data['end_at'],
            ':address_id'    => $data['address_id']    ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare(
            "SELECT a.*,
                    c.first_name AS client_first, c.last_name AS client_last,
                    t.first_name AS tech_first,   t.last_name AS tech_last
             FROM `appointments` a
             LEFT JOIN `users` c ON c.id = a.client_id
             LEFT JOIN `users` t ON t.id = a.technician_id
             WHERE a.id=:id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Appointments for a technician on a given date (YYYY-MM-DD). */
    public static function findByTechnicianDate(int $techId, string $date): array
    {
        $stmt = self::db()->prepare(
            "SELECT a.*,
                    c.first_name AS client_first, c.last_name AS client_last
             FROM `appointments` a
             LEFT JOIN `users` c ON c.id = a.client_id
             WHERE a.technician_id = :tech
               AND DATE(a.start_at) = :date
               AND a.status NOT IN ('cancelled')
             ORDER BY a.start_at ASC"
        );
        $stmt->execute([':tech' => $techId, ':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All appointments for a client. */
    public static function findByClient(int $clientId): array
    {
        $stmt = self::db()->prepare(
            "SELECT a.*,
                    t.first_name AS tech_first, t.last_name AS tech_last
             FROM `appointments` a
             LEFT JOIN `users` t ON t.id = a.technician_id
             WHERE a.client_id=:cid
             ORDER BY a.start_at DESC LIMIT 50"
        );
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether a technician is already booked in the requested window.
     * Returns true if there is a conflict.
     */
    public static function hasConflict(int $techId, string $startAt, string $endAt, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM `appointments`
                WHERE technician_id = :tech
                  AND status NOT IN ('cancelled','no_show')
                  AND start_at < :end
                  AND end_at   > :start";

        if ($excludeId !== null) {
            $sql .= " AND id != :excl";
        }

        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':tech',  $techId,  PDO::PARAM_INT);
        $stmt->bindValue(':start', $startAt, PDO::PARAM_STR);
        $stmt->bindValue(':end',   $endAt,   PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':excl', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function updateStatus(int $id, string $status, ?string $cancelReason = null): void
    {
        $cancelFields = ($status === 'cancelled')
            ? ", cancelled_at=NOW(), cancel_reason=:reason"
            : ", cancel_reason=NULL";

        $stmt = self::db()->prepare(
            "UPDATE `appointments` SET status=:status{$cancelFields}, updated_at=NOW() WHERE id=:id"
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id',     $id,     PDO::PARAM_INT);
        if ($status === 'cancelled') {
            $stmt->bindValue(':reason', $cancelReason, PDO::PARAM_STR);
        }
        $stmt->execute();
    }

    /** All upcoming appointments — admin calendar view. */
    public static function upcoming(int $limit = 100): array
    {
        $stmt = self::db()->prepare(
            "SELECT a.*,
                    c.first_name AS client_first, c.last_name AS client_last,
                    t.first_name AS tech_first,   t.last_name AS tech_last
             FROM `appointments` a
             LEFT JOIN `users` c ON c.id = a.client_id
             LEFT JOIN `users` t ON t.id = a.technician_id
             WHERE a.start_at >= NOW()
               AND a.status NOT IN ('cancelled','completed')
             ORDER BY a.start_at ASC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
