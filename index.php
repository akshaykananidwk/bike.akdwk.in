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
use App\Core\Settings;
use App\Core\Auth;
use App\Core\Request;

// Maintenance mode: block the public site (admins + panel logins still pass).
if (Settings::get('maintenance_mode') === '1') {
    $uri = Request::uri();
    $exempt = preg_match('#^/(admin|shop/login|agency/login|api/)#', $uri) === 1;
    if (!$exempt && !(Auth::check() && Auth::is('super_admin', 'staff'))) {
        http_response_code(503);
        header('Retry-After: 3600');
        $tpl = BASE_PATH . '/app/Views/errors/maintenance.php';
        if (is_file($tpl)) { require $tpl; }
        else { echo 'We are performing maintenance. Please check back shortly.'; }
        exit;
    }
}

$router = new Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch();
