<?php
/**
 * Dwarka Rental — single cron entry point.
 *
 * Add to cPanel cron (every minute recommended):
 *   * * * * * php /home/USER/public_html/cron.php >> /home/USER/public_html/logs/cron.log 2>&1
 *
 * Responsibilities (built out across phases):
 *   - Dispatch the WhatsApp queue (Phase 6)
 *   - Send pickup / return reminders (Phase 6)
 *   - Auto-cancel unpaid bookings past the timeout (Phase 3/4)
 *   - Daily GitHub update check (Phase 8)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !isset($_GET['cron_key'])) {
    // Allow web-trigger only with the configured key (some hosts have no CLI cron).
    // The key check happens after bootstrap below.
}

define('BASE_PATH', __DIR__);

if (!is_file(BASE_PATH . '/config/installed.lock')) {
    fwrite(STDERR, "Not installed.\n");
    exit(1);
}

require BASE_PATH . '/app/bootstrap.php';

use App\Core\Settings;
use App\Core\Cron;

// Web-trigger guard.
if (PHP_SAPI !== 'cli') {
    $key = $_GET['cron_key'] ?? '';
    if (!hash_equals((string)Settings::get('cron_key', ''), (string)$key) || Settings::get('cron_key', '') === '') {
        http_response_code(403);
        exit('Forbidden');
    }
}

$started = microtime(true);
$tasks = [];

if (class_exists(Cron::class)) {
    $tasks = Cron::run();
}

$elapsed = round((microtime(true) - $started) * 1000);
$summary = '[' . date('Y-m-d H:i:s') . "] cron done in {$elapsed}ms: " . json_encode($tasks);
log_line('cron.log', $summary);
if (PHP_SAPI === 'cli') {
    echo $summary . "\n";
}
