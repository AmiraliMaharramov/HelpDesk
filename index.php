<?php
/**
 * QuickFixDesk — Front Controller / Router
 *
 * Bootstraps the application and dispatches requests to
 * the appropriate controller. A lightweight custom router
 * that avoids heavy framework overhead for cPanel hosting.
 */

declare(strict_types=1);

// ── Autoload & Bootstrap ─────────────────────────────────────────────────────
define('ROOT_PATH',    __DIR__);
define('APP_PATH',     ROOT_PATH . '/app');
define('VIEW_PATH',    ROOT_PATH . '/views');
define('LANG_PATH',    ROOT_PATH . '/lang');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH',  ROOT_PATH . '/config');

// Load generated env (written by installer)
if (file_exists(CONFIG_PATH . '/env.php')) {
    require CONFIG_PATH . '/env.php';
}

require CONFIG_PATH . '/app.php';
require CONFIG_PATH . '/database.php';
require APP_PATH    . '/Helpers/Lang.php';

// ── Session ───────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['_last_regen']) || (time() - $_SESSION['_last_regen']) > 300) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}

// ── Language ──────────────────────────────────────────────────────────────────
$lang = $_SESSION['lang']
    ?? ($_COOKIE['lang'] ?? null)
    ?? APP_LANG;

if (!in_array($lang, SUPPORTED_LANGS, true)) {
    $lang = APP_LANG;
}
Lang::load($lang);

// ── Redirect to installer if not yet installed ────────────────────────────────
if (!file_exists(CONFIG_PATH . '/env.php') && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// ── Simple Router ─────────────────────────────────────────────────────────────
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri    = '/' . ltrim(rtrim($uri, '/'), '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

/**
 * Route definitions.
 * Format: [HTTP_METHOD, URI_PATTERN, Controller::class, 'method']
 *
 * URI patterns support:
 *   :id       → numeric segment  (\d+)
 *   :slug     → alphanumeric+hyphen ([a-z0-9-]+)
 *   :any      → anything          ([^/]+)
 */
$routes = [
    // ── Public ─────────────────────────────────────────────────────────────
    ['GET',  '/',                  'PublicController',  'home'],
    ['GET',  '/about',             'PublicController',  'about'],
    ['GET',  '/services',          'PublicController',  'services'],
    ['GET',  '/faq',               'PublicController',  'faq'],
    ['GET',  '/contact',           'PublicController',  'contact'],
    ['POST', '/contact',           'PublicController',  'contactSubmit'],
    ['GET',  '/blog',              'PublicController',  'blog'],
    ['GET',  '/blog/:slug',        'PublicController',  'blogArticle'],

    // ── Auth ───────────────────────────────────────────────────────────────
    ['GET',  '/login',             'AuthController',    'loginForm'],
    ['POST', '/login',             'AuthController',    'login'],
    ['GET',  '/logout',            'AuthController',    'logout'],
    ['GET',  '/register',          'AuthController',    'registerForm'],
    ['POST', '/register',          'AuthController',    'register'],
    ['GET',  '/forgot-password',   'AuthController',    'forgotForm'],
    ['POST', '/forgot-password',   'AuthController',    'forgotSubmit'],
    ['GET',  '/reset-password',    'AuthController',    'resetForm'],
    ['POST', '/reset-password',    'AuthController',    'resetSubmit'],
    ['GET',  '/verify-2fa',        'AuthController',    'verify2faForm'],
    ['POST', '/verify-2fa',        'AuthController',    'verify2fa'],
    ['GET',  '/set-lang/:any',     'AuthController',    'setLang'],

    // ── Client Dashboard ───────────────────────────────────────────────────
    ['GET',  '/dashboard',                  'DashboardController', 'index'],
    ['GET',  '/dashboard/tickets',          'DashboardController', 'tickets'],
    ['GET',  '/dashboard/tickets/new',      'DashboardController', 'newTicket'],
    ['POST', '/dashboard/tickets/new',      'DashboardController', 'createTicket'],
    ['GET',  '/dashboard/tickets/:id',      'DashboardController', 'viewTicket'],
    ['POST', '/dashboard/tickets/:id/reply','DashboardController', 'replyTicket'],
    ['POST', '/dashboard/tickets/:id/survey','DashboardController','submitSurvey'],
    ['GET',  '/dashboard/orders',           'DashboardController', 'orders'],
    ['GET',  '/dashboard/orders/:id',       'DashboardController', 'viewOrder'],
    ['GET',  '/dashboard/invoices',         'DashboardController', 'invoices'],
    ['GET',  '/dashboard/invoices/:id',     'DashboardController', 'viewInvoice'],
    ['GET',  '/dashboard/profile',          'DashboardController', 'profile'],
    ['POST', '/dashboard/profile',          'DashboardController', 'updateProfile'],
    ['GET',  '/dashboard/addresses',        'DashboardController', 'addresses'],
    ['POST', '/dashboard/addresses',        'DashboardController', 'saveAddress'],
    ['POST', '/dashboard/addresses/:id/delete','DashboardController','deleteAddress'],

    // ── Shop ───────────────────────────────────────────────────────────────
    ['GET',  '/shop',                  'ShopController', 'index'],
    ['GET',  '/shop/category/:slug',   'ShopController', 'category'],
    ['GET',  '/shop/product/:slug',    'ShopController', 'product'],
    ['GET',  '/cart',                  'ShopController', 'cart'],
    ['POST', '/cart/add',              'ShopController', 'addToCart'],
    ['POST', '/cart/remove',           'ShopController', 'removeFromCart'],
    ['GET',  '/checkout',              'ShopController', 'checkout'],
    ['POST', '/checkout',              'ShopController', 'placeOrder'],

    // ── Technician Panel ───────────────────────────────────────────────────
    ['GET',  '/technician',                        'TechnicianController', 'dashboard'],
    ['GET',  '/technician/tickets',                'TechnicianController', 'tickets'],
    ['GET',  '/technician/tickets/:id',            'TechnicianController', 'viewTicket'],
    ['POST', '/technician/tickets/:id/status',     'TechnicianController', 'updateStatus'],
    ['POST', '/technician/tickets/:id/reply',      'TechnicianController', 'reply'],
    ['GET',  '/technician/devices',                'TechnicianController', 'devices'],
    ['GET',  '/technician/devices/:id',            'TechnicianController', 'viewDevice'],
    ['POST', '/technician/devices/:id/status',     'TechnicianController', 'updateDeviceStatus'],
    ['GET',  '/technician/devices/:id/qr',         'TechnicianController', 'generateQR'],

    // ── Admin Panel ────────────────────────────────────────────────────────
    ['GET',  '/admin',                       'AdminController', 'dashboard'],
    ['GET',  '/admin/users',                 'AdminController', 'users'],
    ['GET',  '/admin/users/:id',             'AdminController', 'viewUser'],
    ['POST', '/admin/users/:id',             'AdminController', 'updateUser'],
    ['POST', '/admin/users/:id/toggle',      'AdminController', 'toggleUser'],
    ['GET',  '/admin/tickets',               'AdminController', 'tickets'],
    ['GET',  '/admin/devices',               'AdminController', 'devices'],
    ['GET',  '/admin/products',              'AdminController', 'products'],
    ['GET',  '/admin/products/new',          'AdminController', 'newProduct'],
    ['POST', '/admin/products/new',          'AdminController', 'createProduct'],
    ['GET',  '/admin/products/:id/edit',     'AdminController', 'editProduct'],
    ['POST', '/admin/products/:id/edit',     'AdminController', 'updateProduct'],
    ['POST', '/admin/products/:id/delete',   'AdminController', 'deleteProduct'],
    ['GET',  '/admin/orders',                'AdminController', 'orders'],
    ['GET',  '/admin/orders/:id',            'AdminController', 'viewOrder'],
    ['GET',  '/admin/invoices',              'AdminController', 'invoices'],
    ['GET',  '/admin/invoices/:id',          'AdminController', 'viewInvoice'],
    ['GET',  '/admin/campaigns',             'AdminController', 'campaigns'],
    ['POST', '/admin/campaigns',             'AdminController', 'saveCampaign'],
    ['GET',  '/admin/cms/pages',             'AdminController', 'cmsPages'],
    ['GET',  '/admin/cms/sliders',           'AdminController', 'cmsSliders'],
    ['GET',  '/admin/cms/menus',             'AdminController', 'cmsMenus'],
    ['GET',  '/admin/cms/kb',                'AdminController', 'cmsKb'],
    ['GET',  '/admin/cms/faqs',              'AdminController', 'cmsFaqs'],
    ['GET',  '/admin/staff',                 'AdminController', 'staff'],
    ['GET',  '/admin/staff/permissions',     'AdminController', 'permissions'],
    ['GET',  '/admin/routing-rules',         'AdminController', 'routingRules'],
    ['GET',  '/admin/canned-responses',      'AdminController', 'cannedResponses'],
    ['GET',  '/admin/reports',               'AdminController', 'reports'],
    ['GET',  '/admin/audit-logs',            'AdminController', 'auditLogs'],
    ['GET',  '/admin/settings',              'AdminController', 'settings'],
    ['POST', '/admin/settings',              'AdminController', 'saveSettings'],
    ['GET',  '/admin/notifications',         'AdminController', 'notifications'],
];

// ── Dispatch ──────────────────────────────────────────────────────────────────
$matched   = false;
$urlParams = [];

foreach ($routes as [$routeMethod, $pattern, $controllerName, $action]) {
    if ($routeMethod !== $method && !($routeMethod === 'GET' && $method === 'HEAD')) {
        continue;
    }

    // Convert route pattern to regex
    $regex = preg_replace([
        '/:id/',
        '/:slug/',
        '/:any/',
    ], [
        '(\d+)',
        '([a-z0-9-]+)',
        '([^/]+)',
    ], $pattern);

    $regex = '#^' . $regex . '$#i';

    if (preg_match($regex, $uri, $matches)) {
        array_shift($matches);
        $urlParams = $matches;
        $matched   = true;

        // Autoload controller
        $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new $controllerName();
            $controller->$action(...$urlParams);
        } else {
            // Controller not yet implemented — show a friendly placeholder
            http_response_code(200);
            $placeholder = <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8">
<title>QuickFixDesk — Coming Soon</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
<div class="text-center">
  <h1 class="text-2xl font-bold text-slate-800 mb-2">QuickFixDesk</h1>
  <p class="text-slate-500">Module <strong>{$controllerName}::{$action}</strong> is under construction.</p>
  <a href="/" class="mt-4 inline-block text-indigo-600 underline">← Back to Home</a>
</div></body></html>
HTML;
            echo $placeholder;
        }
        break;
    }
}

// ── 404 ───────────────────────────────────────────────────────────────────────
if (!$matched) {
    http_response_code(404);
    if (file_exists(VIEW_PATH . '/public/404.php')) {
        require VIEW_PATH . '/public/404.php';
    } else {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>404 — Not Found</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen">
<div class="text-center">
<h1 class="text-6xl font-black text-slate-300">404</h1>
<p class="text-xl font-semibold text-slate-600 mt-2">Page Not Found</p>
<a href="/" class="mt-4 inline-block text-indigo-600 underline">← Go Home</a>
</div></body></html>';
    }
}
