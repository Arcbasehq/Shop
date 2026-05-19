<?php
declare(strict_types=1);

/**
 * Boots the application: config, autoloader, error handling, session,
 * security headers, DB connection.
 */

namespace Golders;

// ---- Config ----------------------------------------------------------------
$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Missing config/config.php. Copy config/config.example.php and edit it.');
}
$config = require $configPath;

// ---- Error handling --------------------------------------------------------
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
$isDev = ($config['app']['env'] ?? 'production') === 'development';
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('display_startup_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');
error_reporting(E_ALL);

set_exception_handler(function (\Throwable $e) use ($isDev) {
    error_log('[Unhandled] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if ($isDev) {
        echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo 'Internal server error.';
    }
    exit;
});

// ---- Autoloader (PSR-4-ish, single namespace) ------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'Golders\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

// ---- Security headers (set on every response) -----------------------------
Security::sendHeaders();

// ---- Session (hardened) ---------------------------------------------------
$sessionName = $config['session']['name'] ?? 'sid';
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
       || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Idle / absolute session timeout.
$lifetime = (int)($config['session']['lifetime'] ?? 7200);
if (isset($_SESSION['_last_activity']) && time() - $_SESSION['_last_activity'] > $lifetime) {
    $_SESSION = [];
    session_destroy();
    session_start();
    session_regenerate_id(true);
}
$_SESSION['_last_activity'] = time();

// ---- DB --------------------------------------------------------------------
$db = Database::connect($config['db']);

// Make the canonical app URL available to controllers that build absolute
// links (BTCPay redirects). Prefer the configured URL but fall back to the
// request host so the install works before config is fully filled in.
$GLOBALS['_app_url'] = $config['app']['url']
    ?? (($secure ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

return ['config' => $config, 'db' => $db];
