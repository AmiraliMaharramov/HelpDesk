<?php
/**
 * QuickFixDesk — install.php
 *
 * Web-based installer:
 *   Step 1 — System requirements check
 *   Step 2 — Database connection & migration
 *   Step 3 — Admin account creation + site settings
 *   Step 4 — Finish (lock installer)
 *
 * After a successful install, this file renames itself to install.php.done
 * so it cannot be run again without manual intervention.
 */

declare(strict_types=1);

// ── Bootstrap ────────────────────────────────────────────────────────────────
define('INSTALLER', true);
define('ROOT_PATH',   __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('LANG_PATH',   ROOT_PATH . '/lang');
define('APP_LANG',    'en');
define('APP_ENV',     'production');
define('APP_DEBUG',   false);
define('SUPPORTED_LANGS', ['en', 'tr', 'az']);

require CONFIG_PATH . '/app.php';
require ROOT_PATH   . '/app/Helpers/Lang.php';

// Block re-installation
if (file_exists(__DIR__ . '/install.php.done')) {
    http_response_code(403);
    exit('<h2 style="font-family:sans-serif;color:#dc2626">QuickFixDesk is already installed.<br>
          Remove <code>install.php.done</code> to re-run the installer.</h2>');
}

// Load English for installer UI
Lang::load('en');
session_name('qfd_install');
session_start();

$step   = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];

// ── Helper functions ──────────────────────────────────────────────────────────
function req_check(string $ext): bool
{
    return extension_loaded($ext);
}

function pdo_connect(string $host, string $port, string $dbname, string $user, string $pass): PDO
{
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbname}`");

    return $pdo;
}

function run_schema(PDO $pdo): void
{
    $sql = file_get_contents(ROOT_PATH . '/database/schema.sql');

    // Split on semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => $s !== ''
    );

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

function generate_ticket_number(PDO $pdo): string
{
    $year = date('Y');
    $row  = $pdo->query("SELECT COUNT(*) AS cnt FROM tickets")->fetch();
    $seq  = str_pad((string)((int)$row['cnt'] + 1), 6, '0', STR_PAD_LEFT);
    return "TKT-{$year}{$seq}";
}

function html(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Step POST Handlers ────────────────────────────────────────────────────────

// Step 2 — save DB credentials to session, test connection
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = [
        'host'   => trim($_POST['db_host']   ?? 'localhost'),
        'port'   => trim($_POST['db_port']   ?? '3306'),
        'name'   => trim($_POST['db_name']   ?? 'quickfixdesk'),
        'user'   => trim($_POST['db_user']   ?? 'root'),
        'pass'   => $_POST['db_pass'] ?? '',
    ];

    foreach (['host', 'name', 'user'] as $f) {
        if (empty($db[$f])) {
            $errors[] = "Database {$f} is required.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo = pdo_connect($db['host'], $db['port'], $db['name'], $db['user'], $db['pass']);
            $_SESSION['install_db'] = $db;
            header('Location: install.php?step=3');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Connection failed: ' . $e->getMessage();
        }
    }
}

// Step 3 — create admin account, run schema, write config
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['install_db'])) {
        header('Location: install.php?step=2');
        exit;
    }

    $adminFirst = trim($_POST['admin_first']    ?? '');
    $adminLast  = trim($_POST['admin_last']     ?? '');
    $adminEmail = trim($_POST['admin_email']    ?? '');
    $adminPass  = $_POST['admin_password']      ?? '';
    $adminPass2 = $_POST['admin_password2']     ?? '';
    $siteName   = trim($_POST['site_name']      ?? 'QuickFixDesk');
    $siteUrl    = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $siteLang   = $_POST['site_lang']           ?? 'en';
    $siteTz     = $_POST['site_timezone']       ?? 'Europe/Istanbul';

    if (empty($adminFirst)) $errors[] = 'First name is required.';
    if (empty($adminLast))  $errors[] = 'Last name is required.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email required.';
    if (strlen($adminPass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($adminPass !== $adminPass2) $errors[] = 'Passwords do not match.';
    if (!in_array($siteLang, SUPPORTED_LANGS, true)) $siteLang = 'en';

    if (empty($errors)) {
        try {
            $db  = $_SESSION['install_db'];
            $pdo = pdo_connect($db['host'], $db['port'], $db['name'], $db['user'], $db['pass']);

            // Run schema
            run_schema($pdo);

            // Insert admin user
            $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $now  = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("
                INSERT INTO `users`
                    (role_id, first_name, last_name, email, password_hash,
                     is_active, email_verified_at, terms_agreed_at, data_agreed_at, lang, created_at, updated_at)
                VALUES
                    (1, :fn, :ln, :email, :pass, 1, :now, :now, :now, :lang, :now, :now)
            ");
            $stmt->execute([
                ':fn'    => $adminFirst,
                ':ln'    => $adminLast,
                ':email' => $adminEmail,
                ':pass'  => $hash,
                ':now'   => $now,
                ':lang'  => $siteLang,
            ]);

            // Seed site settings
            $settings = [
                'site_name'     => $siteName,
                'site_url'      => $siteUrl,
                'site_lang'     => $siteLang,
                'site_timezone' => $siteTz,
                'installed_at'  => $now,
                'app_version'   => APP_VERSION,
            ];

            $ins = $pdo->prepare("
                INSERT INTO `settings` (setting_key, setting_val)
                VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val)
            ");
            foreach ($settings as $k => $v) {
                $ins->execute([':k' => $k, ':v' => $v]);
            }

            // Write config/.env  (cPanel-friendly: define constants in a file)
            $envContent = <<<ENV
<?php
// Auto-generated by QuickFixDesk Installer — {$now}
// WARNING: Do not commit this file to version control.

define('DB_HOST',    '{$db['host']}');
define('DB_PORT',    '{$db['port']}');
define('DB_NAME',    '{$db['name']}');
define('DB_USER',    '{$db['user']}');
define('DB_PASS',    '{$db['pass']}');
define('APP_URL',    '{$siteUrl}');
define('APP_LANG',   '{$siteLang}');
define('APP_ENV',    'production');
ENV;

            file_put_contents(CONFIG_PATH . '/env.php', $envContent);

            $_SESSION['install_success'] = true;
            $_SESSION['install_db']      = null;

            header('Location: install.php?step=4');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Installation error: ' . $e->getMessage();
        }
    }
}

// Step 4 — lock installer
if ($step === 4 && !empty($_SESSION['install_success'])) {
    // Create lock file to prevent re-installation
    file_put_contents(__DIR__ . '/install.php.done', date('Y-m-d H:i:s'));
    unset($_SESSION['install_success']);
}

// ── Render ────────────────────────────────────────────────────────────────────
$requirements = [
    'PHP 8.0+'        => version_compare(PHP_VERSION, '8.0.0', '>='),
    'PDO'             => req_check('pdo'),
    'PDO MySQL'       => req_check('pdo_mysql'),
    'OpenSSL'         => req_check('openssl'),
    'Mbstring'        => req_check('mbstring'),
    'JSON'            => req_check('json'),
    'GD / Imagick'    => req_check('gd') || req_check('imagick'),
    'Writable config/'=> is_writable(CONFIG_PATH),
    'Writable storage/'=> is_writable(ROOT_PATH . '/storage'),
];

$allPassed = !in_array(false, $requirements, true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuickFixDesk — Installer</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#4f46e5' } } } } }</script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-2xl">
  <!-- Header -->
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 mb-4">
      <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0
             002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0
             001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0
             00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0
             00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0
             00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0
             00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0
             001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07
             2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    </div>
    <h1 class="text-3xl font-bold text-slate-800">QuickFixDesk</h1>
    <p class="text-slate-500 mt-1">Installation Wizard</p>
  </div>

  <!-- Step indicators -->
  <div class="flex items-center justify-center mb-8 gap-2">
    <?php
    $stepLabels = ['Requirements', 'Database', 'Admin Account', 'Finish'];
    foreach ($stepLabels as $i => $label):
        $num    = $i + 1;
        $active = $num === $step;
        $done   = $num < $step;
        $cls    = $active ? 'bg-indigo-600 text-white' : ($done ? 'bg-green-500 text-white' : 'bg-slate-200 text-slate-500');
    ?>
    <div class="flex items-center">
      <div class="flex items-center gap-1.5">
        <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold <?= $cls ?>">
          <?= $done ? '✓' : $num ?>
        </span>
        <span class="hidden sm:inline text-sm <?= $active ? 'font-semibold text-slate-800' : 'text-slate-400' ?>">
          <?= html($label) ?>
        </span>
      </div>
      <?php if ($num < count($stepLabels)): ?>
        <span class="mx-2 text-slate-300">—</span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Card -->
  <div class="bg-white rounded-2xl shadow-lg p-8">

    <?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
      <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
        <?php foreach ($errors as $err): ?>
          <li><?= html($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php /* ── STEP 1: Requirements ── */ if ($step === 1): ?>
    <h2 class="text-xl font-semibold text-slate-800 mb-6">System Requirements</h2>
    <div class="space-y-3 mb-8">
      <?php foreach ($requirements as $name => $pass): ?>
      <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
        <span class="text-slate-700 text-sm"><?= html($name) ?></span>
        <?php if ($pass): ?>
          <span class="inline-flex items-center gap-1 text-green-600 text-sm font-medium">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0
                00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/>
            </svg>Passed
          </span>
        <?php else: ?>
          <span class="inline-flex items-center gap-1 text-red-600 text-sm font-medium">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0
                011.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0
                01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>Failed
          </span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($allPassed): ?>
      <a href="install.php?step=2"
        class="w-full block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
               py-3 px-6 rounded-xl transition-colors">
        Continue →
      </a>
    <?php else: ?>
      <p class="text-sm text-red-600 text-center font-medium">
        Please fix the failed requirements before continuing.
      </p>
    <?php endif; ?>

    <?php /* ── STEP 2: Database ── */ elseif ($step === 2): ?>
    <h2 class="text-xl font-semibold text-slate-800 mb-6">Database Configuration</h2>
    <form method="POST" action="install.php?step=2" class="space-y-5">
      <?php
      $saved = $_SESSION['install_db'] ?? [];
      $fields = [
          ['db_host', 'Database Host', 'localhost', 'text'],
          ['db_port', 'Database Port', '3306',      'number'],
          ['db_name', 'Database Name', 'quickfixdesk', 'text'],
          ['db_user', 'Database Username', 'root',  'text'],
          ['db_pass', 'Database Password', '',      'password'],
      ];
      foreach ($fields as [$name, $label, $placeholder, $type]):
          $val = $saved[$name] ?? '';
      ?>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= html($label) ?></label>
        <input type="<?= $type ?>" name="<?= $name ?>"
               value="<?= $type === 'password' ? '' : html($val) ?>"
               placeholder="<?= html($placeholder) ?>"
               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
      </div>
      <?php endforeach; ?>
      <button type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
               py-3 px-6 rounded-xl transition-colors mt-2">
        Test Connection & Continue →
      </button>
    </form>

    <?php /* ── STEP 3: Admin Account ── */ elseif ($step === 3): ?>
    <h2 class="text-xl font-semibold text-slate-800 mb-6">Admin Account & Site Settings</h2>
    <form method="POST" action="install.php?step=3" class="space-y-5">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
          <input type="text" name="admin_first" required
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
          <input type="text" name="admin_last" required
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Email</label>
        <input type="email" name="admin_email" required
          class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
          <input type="password" name="admin_password" required minlength="8"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password</label>
          <input type="password" name="admin_password2" required minlength="8"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
        </div>
      </div>

      <hr class="border-slate-100">
      <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Site Settings</p>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Name</label>
        <input type="text" name="site_name" value="QuickFixDesk" required
          class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Site URL</label>
        <input type="url" name="site_url" value="<?= html(rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/')) ?>"
          placeholder="https://yourdomain.com" required
          class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Default Language</label>
          <select name="site_lang"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            <option value="en">English</option>
            <option value="tr">Türkçe</option>
            <option value="az">Azərbaycan</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Timezone</label>
          <select name="site_timezone"
            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-slate-800
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            <?php foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $tz): ?>
              <option value="<?= html($tz) ?>" <?= $tz === 'Europe/Istanbul' ? 'selected' : '' ?>>
                <?= html($tz) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
               py-3 px-6 rounded-xl transition-colors mt-2">
        Install QuickFixDesk →
      </button>
    </form>

    <?php /* ── STEP 4: Finish ── */ elseif ($step === 4): ?>
    <div class="text-center py-6">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-slate-800 mb-3">Installation Complete!</h2>
      <p class="text-slate-500 mb-8 max-w-sm mx-auto">
        QuickFixDesk has been successfully installed. The installer has been locked for security.
      </p>
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 text-left">
        <p class="text-sm text-amber-800">
          <strong>Security reminder:</strong> Delete or rename
          <code class="bg-amber-100 px-1 rounded">install.php</code> from your server
          to prevent unauthorized access.
        </p>
      </div>
      <a href="index.php"
        class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
               py-3 px-8 rounded-xl transition-colors">
        Go to QuickFixDesk →
      </a>
    </div>
    <?php endif; ?>

  </div>

  <p class="text-center text-slate-400 text-xs mt-6">
    QuickFixDesk v<?= APP_VERSION ?> · PHP <?= PHP_VERSION ?>
  </p>
</div>

</body>
</html>
