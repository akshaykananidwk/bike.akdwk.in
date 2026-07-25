<?php
/**
 * Dwarka Rental — Front Controller
 *
 * Every non-file HTTP request is routed here by .htaccess. If the app is not
 * yet installed we send the visitor to the /install wizard.
 */

declare(strict_types=1);

define('APP_START', microtime(true));
define('BASE_PATH', __DIR__);

// ---------------------------------------------------------------------------
// Installation gate
// ---------------------------------------------------------------------------
$lockFile = BASE_PATH . '/config/installed.lock';
if (!is_file($lockFile)) {
    // Not installed yet — hand off to the installer (unless already there).
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (strpos($uri, '/install') !== 0) {
        header('Location: install/');
        exit;
    }
    return; // .htaccess serves /install directly; nothing else to do.
}

require BASE_PATH . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch();
