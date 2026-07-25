<?php
/**
 * Application bootstrap — loaded on every installed request and by cron.php.
 * Sets up autoloading, error handling, config, session, DB and helpers.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ---------------------------------------------------------------------------
// PSR-4-ish autoloader for the App\ namespace (app/ directory).
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------
$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration missing. Please run the installer.');
}
$config = require $configFile;
$GLOBALS['app_config'] = $config;

// ---------------------------------------------------------------------------
// Error handling / display
// ---------------------------------------------------------------------------
$debug = (bool)($config['app']['debug'] ?? false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');

set_exception_handler(function (\Throwable $e) use ($debug): void {
    @file_put_contents(
        BASE_PATH . '/logs/app.log',
        '[' . gmdate('Y-m-d H:i:s') . '] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n",
        FILE_APPEND
    );
    http_response_code(500);
    if ($debug) {
        echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        $tpl = BASE_PATH . '/app/Views/errors/500.php';
        if (is_file($tpl)) { require $tpl; } else { echo 'Internal Server Error'; }
    }
});

// ---------------------------------------------------------------------------
// Timezone
// ---------------------------------------------------------------------------
date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Kolkata');

// ---------------------------------------------------------------------------
// Database bootstrap
// ---------------------------------------------------------------------------
App\Core\Database::init($config['db']);

// ---------------------------------------------------------------------------
// Settings (from DB) + secure session
// ---------------------------------------------------------------------------
App\Core\Settings::boot();
App\Core\Session::start($config);
App\Core\Lang::boot();

// ---------------------------------------------------------------------------
// Global helper functions
// ---------------------------------------------------------------------------
require BASE_PATH . '/app/Core/helpers.php';
