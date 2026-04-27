<?php
/**
 * QuickFixDesk — Base Controller
 *
 * Provides shared utilities for all application controllers:
 * view rendering, redirects, flash messages, old-input storage,
 * and authentication guards.
 */

abstract class Controller
{
    // ── View Rendering ────────────────────────────────────────────────────────

    /**
     * Render a view inside an optional layout.
     *
     * The view file is buffered first; its output is captured into
     * $pageContent which the layout can embed wherever it likes.
     * Any key in $data becomes a local variable inside both the
     * view file and the layout file.
     *
     * @param string               $template  e.g. 'auth/login'
     * @param array<string,mixed>  $data      Variables to expose
     * @param string               $layout    Layout name under views/layouts/ ('' = no layout)
     */
    protected function view(string $template, array $data = [], string $layout = 'auth'): void
    {
        // Make all data keys available as local variables
        extract($data, EXTR_SKIP);

        // Capture the inner view
        ob_start();
        require VIEW_PATH . '/' . $template . '.php';
        $pageContent = ob_get_clean();

        if ($layout !== '') {
            require VIEW_PATH . '/layouts/' . $layout . '.php';
        } else {
            echo $pageContent;
        }
    }

    // ── Redirects ─────────────────────────────────────────────────────────────

    /**
     * Redirect to a URL and stop execution.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    // ── Flash Messages ────────────────────────────────────────────────────────

    /**
     * Store a flash message (persists for the next request only).
     *
     * @param string $type    'success' | 'error' | 'info' | 'warning'
     * @param string $message Human-readable message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    /**
     * Retrieve and remove a flash message.
     */
    protected function getFlash(string $type): ?string
    {
        $msg = $_SESSION['_flash'][$type] ?? null;
        unset($_SESSION['_flash'][$type]);
        return $msg;
    }

    // ── Old Input (form repopulation on validation failure) ───────────────────

    /**
     * Store form values in session so they survive a redirect.
     * Passwords are never stored.
     *
     * @param array<string,mixed> $data  Typically a slice of $_POST
     */
    protected function storeOld(array $data): void
    {
        // Never persist sensitive fields
        unset($data['password'], $data['password2'], $data['_csrf']);
        $_SESSION['_old'] = array_map('strval', $data);
    }

    /**
     * Retrieve and remove all stored old-input values.
     *
     * @return array<string,string>
     */
    protected function flushOld(): array
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return $old;
    }

    /**
     * Retrieve and remove field-level validation errors.
     *
     * @return array<string,string>
     */
    protected function flushErrors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $errors;
    }

    // ── Auth Guards ───────────────────────────────────────────────────────────

    /**
     * Redirect authenticated users away from guest-only pages.
     */
    protected function requireGuest(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect($this->dashboardUrl());
        }
    }

    /**
     * Require the user to be authenticated.
     * Optionally restrict to specific role slugs.
     *
     * @param string ...$roles  If provided, at least one must match the user's role
     */
    protected function requireAuth(string ...$roles): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->flash('error', __t('msg_unauthorized'));
            $this->redirect('/login');
        }

        if (!empty($roles) && !in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            $this->redirect($this->dashboardUrl());
        }
    }

    /**
     * Resolve the dashboard URL for the currently authenticated user.
     */
    protected function dashboardUrl(): string
    {
        return match ($_SESSION['user_role'] ?? '') {
            'admin'      => '/admin',
            'technician' => '/technician',
            default      => '/dashboard',
        };
    }

    /**
     * Return basic info about the authenticated user, or null.
     *
     * @return array<string,mixed>|null
     */
    protected function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'      => $_SESSION['user_id'],
            'name'    => $_SESSION['user_name'],
            'email'   => $_SESSION['user_email'],
            'role'    => $_SESSION['user_role'],
            'role_id' => $_SESSION['user_role_id'],
            'avatar'  => $_SESSION['user_avatar'] ?? null,
        ];
    }
}
