<?php
/**
 * QuickFixDesk — CSRF Protection Helper
 *
 * Tokens are stored in the PHP session and validated on every
 * mutating request (POST/PUT/DELETE). A token is generated once
 * per session and reused (single-token strategy), which is safe
 * and allows multi-tab usage.
 */

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Return the current CSRF token, generating one if needed.
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Verify that the given token matches the one in the session.
     * Uses hash_equals() to prevent timing-based side-channel attacks.
     */
    public static function verify(string $token): bool
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? '';

        return $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Render a hidden HTML input field containing the CSRF token.
     * Use inside every <form> tag.
     */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf" value="%s">',
            htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    /**
     * Validate the token submitted in $_POST['_csrf'].
     * On failure, flash an error and redirect back to the previous page.
     * Call this at the top of every POST handler.
     */
    public static function check(): void
    {
        $submitted = $_POST['_csrf'] ?? '';

        if (!self::verify($submitted)) {
            $_SESSION['_flash']['error'] = 'Security token mismatch. Please try the action again.';
            $back = $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $back);
            exit;
        }
    }
}
