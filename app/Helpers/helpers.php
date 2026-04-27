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
