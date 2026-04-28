<?php
/**
 * QuickFixDesk — Global utility helper functions
 *
 * Loaded early in the bootstrap chain (index.php) so these
 * functions are available in every controller, model, and view.
 */

/**
 * HTML-escape a value for safe output in HTML contexts.
 *
 * @param mixed $value  Anything that can be cast to string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return an old (previously submitted) form value, HTML-escaped.
 * The value comes from $_SESSION['_old'] which is populated by
 * Controller::storeOld() on validation failure.
 *
 * @param array<string,string> $old   The old-input array passed to the view
 * @param string               $key   Field name
 * @param string               $default Fallback value
 */
function old(array $old, string $key, string $default = ''): string
{
    return e($old[$key] ?? $default);
}

/**
 * Return whether a field has a validation error.
 *
 * @param array<string,string> $errors
 */
function hasError(array $errors, string $field): bool
{
    return !empty($errors[$field]);
}

/**
 * Return the error message for a field, or empty string.
 *
 * @param array<string,string> $errors
 */
function fieldError(array $errors, string $field): string
{
    return e($errors[$field] ?? '');
}

/**
 * Generate a URL-safe path relative to APP_URL.
 */
function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Sanitise a string — strips tags and trims whitespace.
 */
function clean(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Write a row to the audit_logs table.
 *
 * @param array<string,mixed>|null $oldValues
 * @param array<string,mixed>|null $newValues
 */
function audit(
    string  $action,
    string  $module,
    ?string $entityType  = null,
    ?int    $entityId    = null,
    ?array  $oldValues   = null,
    ?array  $newValues   = null,
    ?int    $userId      = null
): void {
    try {
        $uid = $userId ?? ($_SESSION['user_id'] ?? null);
        db()->prepare(
            "INSERT INTO audit_logs
             (user_id, action, module, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
             VALUES (:uid, :action, :module, :et, :eid, :old, :new, :ip, :ua)"
        )->execute([
            ':uid'    => $uid,
            ':action' => $action,
            ':module' => $module,
            ':et'     => $entityType,
            ':eid'    => $entityId,
            ':old'    => $oldValues  !== null ? json_encode($oldValues)  : null,
            ':new'    => $newValues  !== null ? json_encode($newValues)  : null,
            ':ip'     => $_SERVER['REMOTE_ADDR']   ?? null,
            ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (PDOException $e) {
        error_log('[audit] ' . $e->getMessage());
    }
}

/**
 * Create a web notification for a user.
 *
 * @param array<string,mixed>|null $data  Extra JSON payload
 */
function notify(
    int     $userId,
    string  $type,
    string  $title,
    ?string $body  = null,
    ?array  $data  = null
): void {
    try {
        db()->prepare(
            "INSERT INTO notifications (user_id, type, title, body, data, channel)
             VALUES (:uid, :type, :title, :body, :data, 'web')"
        )->execute([
            ':uid'   => $userId,
            ':type'  => $type,
            ':title' => $title,
            ':body'  => $body,
            ':data'  => $data !== null ? json_encode($data) : null,
        ]);
    } catch (PDOException $e) {
        error_log('[notify] ' . $e->getMessage());
    }
}
