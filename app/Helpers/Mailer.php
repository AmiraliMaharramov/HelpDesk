<?php
/**
 * QuickFixDesk — Mailer Helper
 *
 * Provides a unified email-sending interface that:
 *  1. Uses PHPMailer if it is available (via Composer autoload or manual install).
 *  2. Falls back to PHP's built-in mail() with proper MIME headers.
 *
 * Usage:
 *   Mailer::send('user@example.com', 'Jane Doe', 'Subject', $htmlBody);
 *   Mailer::template('departure', ['name' => 'Jane', 'company' => 'Acme'], $to, $toName);
 */

class Mailer
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Send a plain HTML email.
     *
     * @param string|array $to   Single "email" or ['email'=>'...','name'=>'...']
     */
    public static function send(
        string|array $to,
        string       $subject,
        string       $htmlBody,
        string       $textBody = ''
    ): bool {
        [$toEmail, $toName] = self::parseRecipient($to);

        if ($textBody === '') {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        }

        if (self::phpMailerAvailable()) {
            return self::sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody);
        }

        return self::sendViaMailFunction($toEmail, $toName, $subject, $htmlBody, $textBody);
    }

    /**
     * Render a view-based email template and send it.
     *
     * @param string       $template  File name inside views/mail/ (without .php)
     * @param array<string,mixed> $vars  Variables available in the template
     * @param string|array $to
     */
    public static function template(
        string       $template,
        array        $vars,
        string       $subject,
        string|array $to
    ): bool {
        $html = self::renderTemplate($template, $vars);
        return self::send($to, $subject, $html);
    }

    // ── PHPMailer path ────────────────────────────────────────────────────────

    private static function phpMailerAvailable(): bool
    {
        // Composer install
        if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
            require_once ROOT_PATH . '/vendor/autoload.php';
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return true;
            }
        }

        // Manual install in /lib/PHPMailer
        $manual = ROOT_PATH . '/lib/PHPMailer/src/PHPMailer.php';
        if (file_exists($manual)) {
            require_once ROOT_PATH . '/lib/PHPMailer/src/Exception.php';
            require_once ROOT_PATH . '/lib/PHPMailer/src/PHPMailer.php';
            require_once ROOT_PATH . '/lib/PHPMailer/src/SMTP.php';
            return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
        }

        return false;
    }

    private static function sendViaPHPMailer(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text
    ): bool {
        try {
            $cfg  = self::smtpConfig();
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['user'];
            $mail->Password   = $cfg['pass'];
            $mail->SMTPSecure = $cfg['encryption'];
            $mail->Port       = (int)$cfg['port'];

            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('[Mailer/PHPMailer] ' . $e->getMessage());
            return false;
        }
    }

    // ── PHP mail() fallback ───────────────────────────────────────────────────

    private static function sendViaMailFunction(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        string $text
    ): bool {
        $cfg       = self::smtpConfig();
        $boundary  = md5(uniqid((string)time(), true));
        $fromName  = mb_encode_mimeheader($cfg['from_name'], 'UTF-8', 'B');
        $toDisplay = $toName ? mb_encode_mimeheader($toName, 'UTF-8', 'B') . " <{$toEmail}>" : $toEmail;

        $headers  = "From: {$fromName} <{$cfg['from_email']}>\r\n";
        $headers .= "Reply-To: {$cfg['from_email']}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: QuickFixDesk\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text)) . "\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";

        $body .= "--{$boundary}--\r\n";

        $subjectEncoded = mb_encode_mimeheader($subject, 'UTF-8', 'B');

        $result = @mail($toDisplay, $subjectEncoded, $body, $headers);
        if (!$result) {
            error_log('[Mailer/mail()] Failed to send to ' . $toEmail);
        }
        return $result;
    }

    // ── Template rendering ────────────────────────────────────────────────────

    private static function renderTemplate(string $name, array $vars): string
    {
        $file = VIEW_PATH . '/mail/' . $name . '.php';
        if (!file_exists($file)) {
            return '<p>Email template not found.</p>';
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function parseRecipient(string|array $to): array
    {
        if (is_array($to)) {
            return [$to['email'] ?? '', $to['name'] ?? ''];
        }
        return [$to, ''];
    }

    /**
     * Read SMTP config from the settings table (written by installer),
     * with sensible defaults.
     */
    private static function smtpConfig(): array
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }

        try {
            $rows = db()->query(
                "SELECT setting_key, setting_val FROM settings
                 WHERE setting_key IN
                 ('mail_host','mail_port','mail_user','mail_pass','mail_encryption',
                  'mail_from_email','mail_from_name')"
            )->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $rows = [];
        }

        $cfg = [
            'host'       => $rows['mail_host']        ?? 'localhost',
            'port'       => $rows['mail_port']        ?? 587,
            'user'       => $rows['mail_user']        ?? '',
            'pass'       => $rows['mail_pass']        ?? '',
            'encryption' => $rows['mail_encryption']  ?? 'tls',
            'from_email' => $rows['mail_from_email']  ?? 'no-reply@quickfixdesk.com',
            'from_name'  => $rows['mail_from_name']   ?? 'QuickFixDesk',
        ];

        return $cfg;
    }
}
