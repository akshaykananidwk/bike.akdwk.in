<?php
/**
 * Dwarka Rental — One-Click Installer
 *
 * A guided, session-driven wizard. It runs before the app is configured, so it
 * is deliberately self-contained: it pulls in only the Crypto + Database core
 * classes it needs and writes config/config.php + config/installed.lock on
 * completion. If installed.lock already exists it refuses to run.
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);

session_start();

// Installer support library (defines render_locked, wizard steps, etc.).
require INSTALL_PATH . '/lib.php';

// ---------------------------------------------------------------------------
// Refuse to run if already installed.
// ---------------------------------------------------------------------------
if (is_file(BASE_PATH . '/config/installed.lock')) {
    http_response_code(403);
    render_locked();
    exit;
}

$step = (int)($_GET['step'] ?? 1);
$error = null;
$done = false;

// ---------------------------------------------------------------------------
// Handle POST for the current step.
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($step) {
            case 3: // Database
                $db = [
                    'host'   => trim($_POST['db_host'] ?? 'localhost'),
                    'port'   => (int)($_POST['db_port'] ?? 3306),
                    'name'   => trim($_POST['db_name'] ?? ''),
                    'user'   => trim($_POST['db_user'] ?? ''),
                    'pass'   => (string)($_POST['db_pass'] ?? ''),
                    'prefix' => preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['db_prefix'] ?? 'dwk_'),
                    'charset'=> 'utf8mb4',
                ];
                $seedDemo = !empty($_POST['seed_demo']);
                installer_setup_database($db, $seedDemo); // throws on failure
                $_SESSION['install']['db'] = $db;
                $_SESSION['install']['seed_demo'] = $seedDemo;
                header('Location: ?step=4');
                exit;

            case 4: // Website
                $_SESSION['install']['site'] = [
                    'name'     => trim($_POST['site_name'] ?? 'Dwarka Rental'),
                    'url'      => rtrim(trim($_POST['site_url'] ?? ''), '/'),
                    'timezone' => trim($_POST['timezone'] ?? 'Asia/Kolkata'),
                    'currency' => trim($_POST['currency'] ?? '₹'),
                    'language' => in_array($_POST['language'] ?? 'en', ['en', 'gu'], true) ? $_POST['language'] : 'en',
                ];
                header('Location: ?step=5');
                exit;

            case 5: // Admin account
                $admin = [
                    'name'     => trim($_POST['admin_name'] ?? ''),
                    'mobile'   => trim($_POST['admin_mobile'] ?? ''),
                    'email'    => trim($_POST['admin_email'] ?? ''),
                    'password' => (string)($_POST['admin_password'] ?? ''),
                ];
                if ($admin['name'] === '' || !preg_match('/^[6-9]\d{9}$/', $admin['mobile']) || strlen($admin['password']) < 8) {
                    throw new RuntimeException('Enter a valid name, 10-digit mobile, and a password of at least 8 characters.');
                }
                $_SESSION['install']['admin'] = $admin;
                header('Location: ?step=6');
                exit;

            case 6: // WhatsApp (optional) + finalise
                $_SESSION['install']['wa'] = [
                    'url'     => trim($_POST['wa_url'] ?? ''),
                    'key'     => trim($_POST['wa_key'] ?? ''),
                    'session' => trim($_POST['wa_session'] ?? ''),
                    'sender'  => trim($_POST['wa_sender'] ?? ''),
                ];
                installer_finalize($_SESSION['install']); // writes config + lock + admin
                $done = true;
                header('Location: ?step=7');
                exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

// AJAX endpoints (DB test / WhatsApp test) handled in lib.php-driven actions.
if (isset($_GET['action'])) {
    installer_ajax($_GET['action']);
    exit;
}

// ---------------------------------------------------------------------------
// Render the current step.
// ---------------------------------------------------------------------------
render_layout($step, $error);
