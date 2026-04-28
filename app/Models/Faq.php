<?php
/**
 * QuickFixDesk — Faq Model
 * Handles FULLTEXT search, CRUD, and deflection counting.
 */

require_once APP_PATH . '/Models/Model.php';

class Faq extends Model
{
    protected static string $table = 'faqs';

    /**
     * Search FAQs using FULLTEXT (boolean mode) with LIKE fallback.
     * Returns at most $limit results ordered by relevance.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, string $lang = 'en', int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $db = self::db();

        // FULLTEXT boolean-mode search
        $sql = "SELECT *, MATCH(question, answer) AGAINST (:q IN BOOLEAN MODE) AS relevance
                FROM `faqs`
                WHERE is_active = 1
                  AND (lang = :lang OR lang = 'en')
                  AND MATCH(question, answer) AGAINST (:q2 IN BOOLEAN MODE)
                ORDER BY relevance DESC
                LIMIT :lim";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':q',    $query,     PDO::PARAM_STR);
            $stmt->bindValue(':q2',   $query,     PDO::PARAM_STR);
            $stmt->bindValue(':lang', $lang,      PDO::PARAM_STR);
            $stmt->bindValue(':lim',  $limit,     PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            // FULLTEXT may not be available yet (before migration) — fall through to LIKE
        }

        // LIKE fallback
        $like = '%' . str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query) . '%';
        $sql2 = "SELECT * FROM `faqs`
                 WHERE is_active = 1
                   AND (lang = :lang OR lang = 'en')
                   AND (question LIKE :like OR answer LIKE :like2)
                 ORDER BY sort_order ASC
                 LIMIT :lim";

        $stmt2 = $db->prepare($sql2);
        $stmt2->bindValue(':lang',  $lang,  PDO::PARAM_STR);
        $stmt2->bindValue(':like',  $like,  PDO::PARAM_STR);
        $stmt2->bindValue(':like2', $like,  PDO::PARAM_STR);
        $stmt2->bindValue(':lim',   $limit, PDO::PARAM_INT);
        $stmt2->execute();
        return $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all FAQs ordered by sort_order + id.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::db()
            ->query("SELECT * FROM `faqs` ORDER BY sort_order ASC, id ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single FAQ by id.
     *
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM `faqs` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create a new FAQ entry.
     */
    public static function create(array $data): int
    {
        $db   = self::db();
        $stmt = $db->prepare(
            "INSERT INTO `faqs` (question, answer, category, lang, sort_order, is_active)
             VALUES (:question, :answer, :category, :lang, :sort_order, :is_active)"
        );
        $stmt->execute([
            ':question'   => $data['question'],
            ':answer'     => $data['answer'],
            ':category'   => $data['category'] ?? null,
            ':lang'       => $data['lang']      ?? 'en',
            ':sort_order' => (int)($data['sort_order'] ?? 0),
            ':is_active'  => (int)($data['is_active']  ?? 1),
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Update an existing FAQ.
     */
    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            "UPDATE `faqs`
             SET question=:question, answer=:answer, category=:category,
                 lang=:lang, sort_order=:sort_order, is_active=:is_active
             WHERE id=:id"
        );
        $stmt->execute([
            ':question'   => $data['question'],
            ':answer'     => $data['answer'],
            ':category'   => $data['category'] ?? null,
            ':lang'       => $data['lang']      ?? 'en',
            ':sort_order' => (int)($data['sort_order'] ?? 0),
            ':is_active'  => (int)($data['is_active']  ?? 1),
            ':id'         => $id,
        ]);
    }

    /**
     * Delete a FAQ.
     */
    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare("DELETE FROM `faqs` WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * Count deflections logged for this FAQ in the last 7 days.
     */
    public static function deflectionCount(int $faqId): int
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM `audit_logs`
             WHERE action = 'faq_deflection'
               AND entity_type = 'faq'
               AND entity_id = :faq_id
               AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $stmt->execute([':faq_id' => $faqId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Total deflections in the last 7 days (all FAQs).
     */
    public static function totalDeflectionsThisWeek(): int
    {
        $stmt = self::db()->query(
            "SELECT COUNT(*) FROM `audit_logs`
             WHERE action = 'faq_deflection'
               AND entity_type = 'faq'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        return (int)$stmt->fetchColumn();
    }
}
