<?php
/**
 * QuickFixDesk — Application Configuration
 */

define('APP_NAME',    'QuickFixDesk');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'production');  // development | production
define('APP_DEBUG',   APP_ENV === 'development');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost');
define('APP_LANG',    'en');   // default language: en | tr | az

define('SUPPORTED_LANGS', ['en', 'tr', 'az']);

define('ROOT_PATH',    __DIR__ . '/..');
define('APP_PATH',     ROOT_PATH . '/app');
define('VIEW_PATH',    ROOT_PATH . '/views');
define('LANG_PATH',    ROOT_PATH . '/lang');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH',  __DIR__);

// Session configuration
define('SESSION_NAME',     'qfd_session');
define('SESSION_LIFETIME', 7200);  // 2 hours

// Upload limits
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);  // 10 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination
define('PER_PAGE_DEFAULT', 20);

// Mail (override via settings table at runtime)
define('MAIL_HOST',       getenv('MAIL_HOST')       ?: 'localhost');
define('MAIL_PORT',       getenv('MAIL_PORT')       ?: 587);
define('MAIL_USERNAME',   getenv('MAIL_USERNAME')   ?: '');
define('MAIL_PASSWORD',   getenv('MAIL_PASSWORD')   ?: '');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@quickfixdesk.com');
define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: APP_NAME);

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Istanbul');

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
