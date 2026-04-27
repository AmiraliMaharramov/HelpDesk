<?php
/**
 * QuickFixDesk — Auth Controller
 *
 * Handles: login, logout, individual & corporate registration,
 * forgot-password with 6-digit 2FA verification, and password reset.
 */

require_once APP_PATH . '/Controllers/Controller.php';
require_once APP_PATH . '/Models/User.php';
require_once APP_PATH . '/Helpers/Csrf.php';

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        $this->requireGuest();

        $this->view('auth/login', [
            'pageTitle' => __t('login_title'),
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
            'old'       => $this->flushOld(),
        ]);
    }

    public function login(): void
    {
        $this->requireGuest();
        Csrf::check();

        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $remember = !empty($_POST['remember']);

        $errors = [];

        if ($email === '') {
            $errors[] = __t('validation_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __t('validation_email');
        }

        if ($password === '') {
            $errors[] = __t('validation_required');
        }

        if (empty($errors)) {
            $user = $this->userModel->findByEmail($email);

            if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
                // Intentionally vague to prevent user-enumeration
                $errors[] = __t('auth_invalid_credentials');
            } elseif (!$user['is_active']) {
                $errors[] = __t('auth_account_inactive');
            } elseif (in_array($user['role_slug'], ['admin', 'technician'], true)) {
                // Staff: enforce working-hour login restriction
                $now   = new DateTime('now');
                $start = DateTime::createFromFormat('H:i:s', $user['work_start'] ?? '09:00:00');
                $end   = DateTime::createFromFormat('H:i:s', $user['work_end']   ?? '18:00:00');

                if ($start && $end && ($now < $start || $now > $end)) {
                    $errors[] = __t('msg_invalid_hours');
                }
            }
        }

        if (!empty($errors)) {
            $this->storeOld(['email' => $email]);
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/login');
        }

        // $user is guaranteed to be set and valid at this point
        /** @var array<string,mixed> $user */
        $this->createSession($user);

        if ($remember) {
            $this->setRememberCookie($user);
        }

        $this->writeAuditLog('login', 'auth', 'user', (int)$user['id'], null, null, (int)$user['id']);

        $this->redirect($this->dashboardUrl());
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $this->writeAuditLog(
            'logout', 'auth', 'user',
            (int)($_SESSION['user_id'] ?? 0),
            null, null,
            (int)($_SESSION['user_id'] ?? 0)
        );

        session_unset();
        session_destroy();

        // Start a fresh session just to carry the flash
        session_start();
        $this->flash('success', __t('auth_logout_success'));

        $this->redirect('/login');
    }

    // ── Registration ──────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        $this->requireGuest();

        $this->view('auth/register', [
            'pageTitle' => __t('register_title'),
            'error'     => $this->getFlash('error'),
            'success'   => $this->getFlash('success'),
            'errors'    => $this->flushErrors(),
            'old'       => $this->flushOld(),
        ]);
    }

    public function register(): void
    {
        $this->requireGuest();
        Csrf::check();

        // Collect input
        $type             = $_POST['type']             ?? 'individual';
        $firstName        = trim($_POST['first_name']        ?? '');
        $lastName         = trim($_POST['last_name']         ?? '');
        $email            = trim(strtolower($_POST['email']  ?? ''));
        $phone            = trim($_POST['phone']             ?? '');
        $password         = $_POST['password']               ?? '';
        $password2        = $_POST['password2']              ?? '';
        $terms            = !empty($_POST['terms']);
        $dataAgree        = !empty($_POST['data_agreement']);
        // Corporate
        $companyName      = trim($_POST['company_name']      ?? '');
        $taxId            = trim($_POST['tax_id']            ?? '');
        $taxOffice        = trim($_POST['tax_office']        ?? '');
        $authorizedPerson = trim($_POST['authorized_person'] ?? '');

        $errors = [];

        // ── Common validation ─────────────────────────────────────────────────
        if ($firstName === '') {
            $errors['first_name'] = __t('validation_required');
        }
        if ($lastName === '') {
            $errors['last_name'] = __t('validation_required');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __t('validation_email');
        }
        if (strlen($password) < 8) {
            $errors['password'] = __t('validation_min_length', ['min' => '8']);
        }
        if ($password !== $password2) {
            $errors['password2'] = __t('validation_password_match');
        }
        if (!$terms) {
            $errors['terms'] = __t('validation_terms');
        }
        if (!$dataAgree) {
            $errors['data_agreement'] = __t('auth_data_agreement_required');
        }

        // ── Corporate-only validation ─────────────────────────────────────────
        if ($type === 'corporate') {
            if ($companyName === '') {
                $errors['company_name'] = __t('validation_required');
            }
            if ($taxId === '') {
                $errors['tax_id'] = __t('validation_required');
            }
            if ($taxOffice === '') {
                $errors['tax_office'] = __t('validation_required');
            }
            if ($authorizedPerson === '') {
                $errors['authorized_person'] = __t('validation_required');
            }
        }

        // ── Email uniqueness ──────────────────────────────────────────────────
        if (!isset($errors['email']) && $this->userModel->emailExists($email)) {
            $errors['email'] = __t('auth_email_taken');
        }

        if (!empty($errors)) {
            $this->storeOld([
                'type'              => $type,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'phone'             => $phone,
                'company_name'      => $companyName,
                'tax_id'            => $taxId,
                'tax_office'        => $taxOffice,
                'authorized_person' => $authorizedPerson,
            ]);
            $_SESSION['_errors'] = $errors;
            $this->redirect('/register');
        }

        // role_id: 3 = corporate client, 4 = individual client
        $roleId = ($type === 'corporate') ? 3 : 4;
        $now    = date('Y-m-d H:i:s');

        $userId = $this->userModel->create([
            'role_id'           => $roleId,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'phone'             => $phone,
            'password_hash'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'company_name'      => $type === 'corporate' ? $companyName      : null,
            'tax_id'            => $type === 'corporate' ? $taxId            : null,
            'tax_office'        => $type === 'corporate' ? $taxOffice        : null,
            'authorized_person' => $type === 'corporate' ? $authorizedPerson : null,
            'terms_agreed_at'   => $now,
            'data_agreed_at'    => $now,
            'lang'              => Lang::current(),
        ]);

        $this->writeAuditLog('register', 'auth', 'user', $userId, null, null, $userId);
        $this->flash('success', __t('auth_register_success'));
        $this->redirect('/login');
    }

    // ── Forgot Password ───────────────────────────────────────────────────────

    public function forgotForm(): void
    {
        $this->requireGuest();

        $this->view('auth/forgot', [
            'pageTitle' => __t('forgot_title'),
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
            'old'       => $this->flushOld(),
        ]);
    }

    public function forgotSubmit(): void
    {
        $this->requireGuest();
        Csrf::check();

        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->storeOld(['email' => $email]);
            $this->flash('error', __t('validation_email'));
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && $user['is_active']) {
            $token = bin2hex(random_bytes(32));
            // 6-digit zero-padded code
            $code  = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $this->userModel->createPasswordReset((int)$user['id'], $token, $code);

            // Store the token in session so the verify-2fa step can look it up
            $_SESSION['_reset_token'] = $token;
            $_SESSION['_reset_email'] = $email;

            // TODO: Send $code to $email via SMTP
            // In development mode, store code in session so it can be read without email
            if (APP_DEBUG) {
                $_SESSION['_reset_debug_code'] = $code;
            }
        } else {
            // Always continue to the 2FA page regardless of whether the email exists
            // to prevent email-enumeration attacks
            $_SESSION['_reset_token'] = bin2hex(random_bytes(32));
            $_SESSION['_reset_email'] = $email;
        }

        $this->flash('success', __t('auth_reset_sent'));
        $this->redirect('/verify-2fa');
    }

    // ── 2FA Verification (password reset flow) ────────────────────────────────

    public function verify2faForm(): void
    {
        if (empty($_SESSION['_reset_token'])) {
            $this->redirect('/forgot-password');
        }

        $this->view('auth/verify-2fa', [
            'pageTitle' => __t('forgot_2fa_title'),
            'error'     => $this->getFlash('error'),
            'success'   => $this->getFlash('success'),
            'email'     => $_SESSION['_reset_email'] ?? '',
        ]);
    }

    public function verify2fa(): void
    {
        if (empty($_SESSION['_reset_token'])) {
            $this->redirect('/forgot-password');
        }

        Csrf::check();

        $code  = trim($_POST['code'] ?? '');
        $token = $_SESSION['_reset_token'];

        // Must be exactly 6 decimal digits
        if (!ctype_digit($code) || strlen($code) !== 6) {
            $this->flash('error', __t('auth_2fa_invalid'));
            $this->redirect('/verify-2fa');
        }

        $reset = $this->userModel->findPasswordReset($token);

        if (!$reset || !hash_equals($reset['totp_code'], $code)) {
            $this->flash('error', __t('auth_2fa_invalid'));
            $this->redirect('/verify-2fa');
        }

        // Code is correct — mark the session as verified for the reset step
        $_SESSION['_reset_verified'] = true;

        $this->redirect('/reset-password');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function resetForm(): void
    {
        if (empty($_SESSION['_reset_token']) || empty($_SESSION['_reset_verified'])) {
            $this->redirect('/forgot-password');
        }

        $this->view('auth/reset-password', [
            'pageTitle' => __t('reset_password_title'),
            'error'     => $this->getFlash('error'),
            'success'   => $this->getFlash('success'),
        ]);
    }

    public function resetSubmit(): void
    {
        if (empty($_SESSION['_reset_token']) || empty($_SESSION['_reset_verified'])) {
            $this->redirect('/forgot-password');
        }

        Csrf::check();

        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';
        $token     = $_SESSION['_reset_token'];

        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = __t('validation_min_length', ['min' => '8']);
        }
        if ($password !== $password2) {
            $errors[] = __t('validation_password_match');
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/reset-password');
        }

        // Re-verify the token hasn't expired between the verify and reset steps
        $reset = $this->userModel->findPasswordReset($token);

        if (!$reset) {
            // Token expired or already consumed
            unset($_SESSION['_reset_token'], $_SESSION['_reset_email'], $_SESSION['_reset_verified']);
            $this->flash('error', __t('auth_session_expired'));
            $this->redirect('/forgot-password');
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userModel->updatePassword((int)$reset['user_id'], $newHash);
        $this->userModel->markPasswordResetUsed($token);

        unset($_SESSION['_reset_token'], $_SESSION['_reset_email'], $_SESSION['_reset_verified']);

        $this->writeAuditLog('password_reset', 'auth', 'user', (int)$reset['user_id'], null, null, (int)$reset['user_id']);
        $this->flash('success', __t('auth_reset_success'));
        $this->redirect('/login');
    }

    // ── Language Switcher ─────────────────────────────────────────────────────

    public function setLang(string $lang): void
    {
        if (in_array($lang, SUPPORTED_LANGS, true)) {
            $_SESSION['lang'] = $lang;
            setcookie(
                'lang', $lang,
                time() + (86400 * 30),
                '/', '',
                isset($_SERVER['HTTPS']),
                true
            );
        }

        // Redirect back to the page the user came from
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    /**
     * Populate the session with the authenticated user's data.
     *
     * @param array<string,mixed> $user
     */
    private function createSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email']   = $user['email'];
        $_SESSION['user_role']    = $user['role_slug'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_avatar']  = $user['avatar'] ?? null;
        $_SESSION['_last_regen']  = time();

        // Restore the user's preferred UI language
        if (!empty($user['lang'])) {
            $_SESSION['lang'] = $user['lang'];
            Lang::load($user['lang']);
        }
    }

    /**
     * Set a long-lived "remember me" cookie backed by a hashed token in the DB.
     *
     * @param array<string,mixed> $user
     */
    private function setRememberCookie(array $user): void
    {
        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $this->userModel->saveRememberToken((int)$user['id'], $hashedToken);

        setcookie(
            'remember',
            $rawToken,
            time() + (86400 * 30),  // 30 days
            '/', '',
            isset($_SERVER['HTTPS']),
            true
        );
    }

    /**
     * Insert a row into audit_logs. Fails silently so it never
     * disrupts the user-facing request.
     *
     * @param array<string,mixed>|null $old
     * @param array<string,mixed>|null $new
     */
    private function writeAuditLog(
        string  $action,
        string  $module,
        string  $entityType,
        int     $entityId,
        ?array  $old,
        ?array  $new,
        ?int    $userId = null
    ): void {
        try {
            db()->prepare(
                "INSERT INTO audit_logs
                    (user_id, action, module, entity_type, entity_id,
                     old_values, new_values, ip_address, user_agent)
                 VALUES
                    (:uid, :action, :module, :entity_type, :entity_id,
                     :old, :new, :ip, :ua)"
            )->execute([
                ':uid'         => $userId,
                ':action'      => $action,
                ':module'      => $module,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':old'         => $old !== null ? json_encode($old) : null,
                ':new'         => $new !== null ? json_encode($new) : null,
                ':ip'          => $_SERVER['REMOTE_ADDR']    ?? null,
                ':ua'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable) {
            // Audit logging failure must not break the main request
        }
    }
}
