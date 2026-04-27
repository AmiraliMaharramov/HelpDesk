<?php
/**
 * QuickFixDesk — User Model
 *
 * Handles all database interactions for the `users` table
 * and the related `password_resets` table.
 */

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = db();
    }

    // ── Lookups ───────────────────────────────────────────────────────────────

    /**
     * Find a user by email address.
     * Also joins the role slug for convenient access.
     *
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.slug AS role_slug
             FROM   users u
             JOIN   roles r ON r.id = u.role_id
             WHERE  u.email = :email
             LIMIT  1"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a user by primary key.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.slug AS role_slug
             FROM   users u
             JOIN   roles r ON r.id = u.role_id
             WHERE  u.id = :id
             LIMIT  1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Return true if the given email address is already taken.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE email = :email"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Creation ──────────────────────────────────────────────────────────────

    /**
     * Insert a new user row and return the auto-increment ID.
     *
     * Expected keys in $data:
     *   role_id, first_name, last_name, email, phone (nullable),
     *   password_hash, company_name, tax_id, tax_office,
     *   authorized_person, terms_agreed_at, data_agreed_at, lang
     *
     * @param  array<string,mixed> $data
     * @return int  The new user's ID
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users
                (role_id, first_name, last_name, email, phone, password_hash,
                 company_name, tax_id, tax_office, authorized_person,
                 terms_agreed_at, data_agreed_at, lang)
             VALUES
                (:role_id, :first_name, :last_name, :email, :phone, :password_hash,
                 :company_name, :tax_id, :tax_office, :authorized_person,
                 :terms_agreed_at, :data_agreed_at, :lang)"
        );

        $stmt->execute([
            ':role_id'           => $data['role_id'],
            ':first_name'        => $data['first_name'],
            ':last_name'         => $data['last_name'],
            ':email'             => strtolower(trim($data['email'])),
            ':phone'             => $data['phone']             ?: null,
            ':password_hash'     => $data['password_hash'],
            ':company_name'      => $data['company_name']      ?? null,
            ':tax_id'            => $data['tax_id']            ?? null,
            ':tax_office'        => $data['tax_office']        ?? null,
            ':authorized_person' => $data['authorized_person'] ?? null,
            ':terms_agreed_at'   => $data['terms_agreed_at']   ?? null,
            ':data_agreed_at'    => $data['data_agreed_at']    ?? null,
            ':lang'              => $data['lang']              ?? 'en',
        ]);

        return (int) $this->db->lastInsertId();
    }

    // ── Password Handling ─────────────────────────────────────────────────────

    /**
     * Verify a plain-text password against a stored bcrypt hash.
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /**
     * Update a user's password hash.
     */
    public function updatePassword(int $userId, string $newHash): void
    {
        $this->db->prepare(
            "UPDATE users
             SET    password_hash = :hash, updated_at = NOW()
             WHERE  id = :id"
        )->execute([':hash' => $newHash, ':id' => $userId]);
    }

    // ── Password-Reset / 2FA ──────────────────────────────────────────────────

    /**
     * Create a password-reset record.
     * Deletes any previous unexpired reset for the same user first.
     *
     * @param int    $userId  The user requesting the reset
     * @param string $token   A 64-char hex security token (stored in session)
     * @param string $code    A 6-digit numeric code (sent to the user's email)
     */
    public function createPasswordReset(int $userId, string $token, string $code): void
    {
        // Remove any existing pending reset for this user
        $this->db->prepare(
            "DELETE FROM password_resets WHERE user_id = :id"
        )->execute([':id' => $userId]);

        $this->db->prepare(
            "INSERT INTO password_resets (user_id, token, totp_code, expires_at)
             VALUES (:user_id, :token, :code,
                     DATE_ADD(NOW(), INTERVAL 15 MINUTE))"
        )->execute([
            ':user_id' => $userId,
            ':token'   => $token,
            ':code'    => $code,
        ]);
    }

    /**
     * Find a valid (un-expired, un-used) password-reset record by token.
     *
     * @return array<string,mixed>|null
     */
    public function findPasswordReset(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*, u.email, u.first_name
             FROM   password_resets pr
             JOIN   users u ON u.id = pr.user_id
             WHERE  pr.token      = :token
               AND  pr.used_at   IS NULL
               AND  pr.expires_at > NOW()
             LIMIT  1"
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Mark a password-reset record as used (consumed).
     */
    public function markPasswordResetUsed(string $token): void
    {
        $this->db->prepare(
            "UPDATE password_resets
             SET    used_at = NOW()
             WHERE  token   = :token"
        )->execute([':token' => $token]);
    }

    /**
     * Store a hashed "remember me" token against the user record.
     */
    public function saveRememberToken(int $userId, string $hashedToken): void
    {
        $this->db->prepare(
            "UPDATE users
             SET    remember_token = :token, updated_at = NOW()
             WHERE  id = :id"
        )->execute([':token' => $hashedToken, ':id' => $userId]);
    }
}
