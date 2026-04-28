<?php
/**
 * QuickFixDesk — Department Model
 */

require_once APP_PATH . '/Models/Model.php';

class Department extends Model
{
    protected static string $table = 'departments';

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return self::db()
            ->query("SELECT * FROM `departments` ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allActive(): array
    {
        return self::db()
            ->query("SELECT * FROM `departments` WHERE is_active=1 ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM `departments` WHERE id=:id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM `departments` WHERE slug=:slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int
    {
        $db   = self::db();
        $stmt = $db->prepare(
            "INSERT INTO `departments` (name, slug, description, is_active)
             VALUES (:name, :slug, :description, :is_active)"
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':slug'        => $data['slug'] ?? self::slugify($data['name']),
            ':description' => $data['description'] ?? null,
            ':is_active'   => (int)($data['is_active'] ?? 1),
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare(
            "UPDATE `departments`
             SET name=:name, slug=:slug, description=:description, is_active=:is_active
             WHERE id=:id"
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':slug'        => $data['slug'] ?? self::slugify($data['name']),
            ':description' => $data['description'] ?? null,
            ':is_active'   => (int)($data['is_active'] ?? 1),
            ':id'          => $id,
        ]);
    }

    /**
     * Delete a department only if it has no staff assigned.
     * Returns true on success, false if staff are present.
     */
    public static function delete(int $id): bool
    {
        $count = self::staffCount($id);
        if ($count > 0) {
            return false;
        }
        $stmt = self::db()->prepare("DELETE FROM `departments` WHERE id=:id");
        $stmt->execute([':id' => $id]);
        return true;
    }

    public static function staffCount(int $id): int
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM `users` WHERE department_id=:id AND is_active=1"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    private static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        return trim($text, '-');
    }
}
