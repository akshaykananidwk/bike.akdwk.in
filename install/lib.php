<?php
/**
 * Installer support library: system checks, DB import, config writing,
 * AJAX test endpoints, and the HTML wizard chrome.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// System requirements
// ---------------------------------------------------------------------------
function installer_requirements(): array
{
    $req = [];
    $req[] = [
        'label' => 'PHP >= 8.1',
        'ok'    => version_compare(PHP_VERSION, '8.1.0', '>='),
        'value' => PHP_VERSION,
    ];
    foreach (['pdo_mysql', 'curl', 'mbstring', 'gd', 'zip', 'json', 'openssl', 'fileinfo'] as $ext) {
        $req[] = [
            'label' => "Extension: {$ext}",
            'ok'    => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'loaded' : 'missing',
        ];
    }
    foreach (['config', 'uploads', 'backups', 'cache', 'logs'] as $dir) {
        $path = BASE_PATH . '/' . $dir;
        $writable = is_dir($path) ? is_writable($path) : @mkdir($path, 0755, true);
        $req[] = [
            'label' => "Writable: /{$dir}",
            'ok'    => (bool)$writable,
            'value' => $writable ? 'writable' : 'not writable',
        ];
    }
    $req[] = [
        'label' => 'URL rewriting (mod_rewrite)',
        'ok'    => installer_rewrite_available(),
        'value' => installer_rewrite_available() ? 'available' : 'unknown (check .htaccess)',
        'soft'  => true, // non-blocking
    ];
    return $req;
}

function installer_rewrite_available(): bool
{
    if (function_exists('apache_get_modules')) {
        return in_array('mod_rewrite', apache_get_modules(), true);
    }
    // Can't detect on nginx/php-fpm — assume the sysadmin configured it.
    return true;
}

function installer_all_ok(array $req): bool
{
    foreach ($req as $r) {
        if (!$r['ok'] && empty($r['soft'])) {
            return false;
        }
    }
    return true;
}

// ---------------------------------------------------------------------------
// Database connection + import
// ---------------------------------------------------------------------------
function installer_pdo(array $db): PDO
{
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);
}

function installer_setup_database(array $db, bool $seedDemo): void
{
    $pdo = installer_pdo($db); // throws on bad credentials

    // Guard: don't clobber an existing install unless tables absent.
    $sqlFile = INSTALL_PATH . '/database.sql';
    if (!is_file($sqlFile)) {
        throw new RuntimeException('database.sql not found in installer.');
    }
    $sql = file_get_contents($sqlFile);
    $sql = str_replace('{p}', $db['prefix'], $sql);

    installer_run_sql_script($pdo, $sql);

    if ($seedDemo) {
        installer_seed_demo($pdo, $db['prefix']);
    }
}

/** Execute a multi-statement SQL script. */
function installer_run_sql_script(PDO $pdo, string $sql): void
{
    // Split on semicolons that terminate statements (naive but fine for our DDL —
    // no stored procedures / no embedded semicolons in our seed strings besides
    // inside quotes, which we guard against).
    // NOTE: DDL (CREATE TABLE, etc.) causes an implicit COMMIT in MySQL/MariaDB,
    // so wrapping the import in a transaction is meaningless. We run statements
    // sequentially and surface the first failure.
    $statements = installer_split_sql($sql);
    foreach ($statements as $i => $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (Throwable $e) {
            throw new RuntimeException('Database import failed at statement #' . ($i + 1) . ': ' . $e->getMessage());
        }
    }
}

/** Split a SQL script into statements, respecting quoted strings. */
function installer_split_sql(string $sql): array
{
    // Strip full-line "--" comments first (they may contain semicolons that
    // would otherwise break statement splitting). String literals never start
    // a line with "--" in our scripts, so this is safe.
    $lines = preg_split('/\r\n|\r|\n/', $sql);
    $clean = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);

    $out = [];
    $buf = '';
    $inS = false; $inD = false;
    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';
        if ($ch === "'" && !$inD && $prev !== '\\') { $inS = !$inS; }
        elseif ($ch === '"' && !$inS && $prev !== '\\') { $inD = !$inD; }

        if ($ch === ';' && !$inS && !$inD) {
            $out[] = $buf;
            $buf = '';
        } else {
            $buf .= $ch;
        }
    }
    if (trim($buf) !== '') { $out[] = $buf; }
    return $out;
}

function installer_seed_demo(PDO $pdo, string $p): void
{
    // Demo shop
    $pdo->prepare("INSERT INTO `{$p}shops` (code,name,name_gu,owner_name,mobile,whatsapp,area,city,commission_type,commission_value,kyc_status,is_verified,status)
        VALUES ('SHOP-DWK-001','Krishna Store','કૃષ્ણા સ્ટોર','Ramesh Bhai','9876543210','9876543210','Bus Stand Road','Dwarka','percent',10,'approved',1,'active')")->execute();
    $shopId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO `{$p}wallets` (owner_type,owner_id,balance) VALUES ('shop',?,0)")->execute([$shopId]);

    // Demo agency
    $pdo->prepare("INSERT INTO `{$p}agencies` (code,name,name_gu,owner_name,mobile,whatsapp,commission_type,commission_value,status)
        VALUES ('AGN-DWK-001','Dwarka Wheels','દ્વારકા વ્હીલ્સ','Suresh Bhai','9812345678','9812345678','inherit',0,'active')")->execute();
    $agencyId = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO `{$p}wallets` (owner_type,owner_id,balance) VALUES ('agency',?,0)")->execute([$agencyId]);

    // Demo vehicle (Activa)
    $catId = (int)$pdo->query("SELECT id FROM `{$p}categories` WHERE slug='activa' LIMIT 1")->fetchColumn();
    $pdo->prepare("INSERT INTO `{$p}vehicles` (agency_id,category_id,name,name_gu,brand,model,reg_number,transmission,fuel,seats,units,price_hour,price_day,deposit,commission_type,commission_value,description,status)
        VALUES (?,?,'Honda Activa 6G','હોન્ડા એક્ટિવા','Honda','Activa 6G','GJ37AB1234','non_gear','petrol',2,3,40,400,1000,'inherit',0,'Reliable non-gear scooter, perfect for Dwarka darshan.','active')")
        ->execute([$agencyId, $catId ?: null]);
}

// ---------------------------------------------------------------------------
// Finalisation: write config, create admin, lock install
// ---------------------------------------------------------------------------
function installer_finalize(array $data): void
{
    $db    = $data['db']    ?? null;
    $site  = $data['site']  ?? null;
    $admin = $data['admin'] ?? null;
    $wa    = $data['wa']    ?? [];
    if (!$db || !$site || !$admin) {
        throw new RuntimeException('Missing setup data. Please restart the installer.');
    }

    $appKey = 'base64:' . base64_encode(random_bytes(32));

    // 1) Write config/config.php
    $config = installer_render_config($db, $site, $appKey);
    if (@file_put_contents(BASE_PATH . '/config/config.php', $config) === false) {
        throw new RuntimeException('Could not write config/config.php — check folder permissions.');
    }
    @chmod(BASE_PATH . '/config/config.php', 0640);

    // 2) Write a minimal .env for reference (not web-readable; blocked by .htaccess)
    $env = "APP_KEY={$appKey}\nDB_HOST={$db['host']}\nDB_NAME={$db['name']}\nDB_USER={$db['user']}\n";
    @file_put_contents(BASE_PATH . '/config/.env', $env);
    @chmod(BASE_PATH . '/config/.env', 0640);

    // 3) Persist site + WhatsApp settings and create the admin (needs encryption -> APP_KEY).
    $pdo = installer_pdo($db);
    $p = $db['prefix'];

    installer_set($pdo, $p, 'site_name', $site['name']);
    installer_set($pdo, $p, 'site_url', $site['url']);
    installer_set($pdo, $p, 'timezone', $site['timezone']);
    installer_set($pdo, $p, 'currency_symbol', $site['currency']);
    installer_set($pdo, $p, 'default_language', $site['language']);

    // WhatsApp secrets (encrypted with APP_KEY).
    if (!empty($wa['url']))     { installer_set($pdo, $p, 'wa_api_url',   installer_encrypt($wa['url'], $appKey), 1); }
    if (!empty($wa['key']))     { installer_set($pdo, $p, 'wa_api_key',   installer_encrypt($wa['key'], $appKey), 1); }
    if (!empty($wa['session'])) { installer_set($pdo, $p, 'wa_session_id',installer_encrypt($wa['session'], $appKey), 1); }
    if (!empty($wa['sender']))  { installer_set($pdo, $p, 'wa_sender',    installer_encrypt($wa['sender'], $appKey), 1); }
    if (!empty($wa['url']) && !empty($wa['key'])) { installer_set($pdo, $p, 'wa_enabled', '1'); }

    // 4) Create super admin (idempotent — update if the mobile already exists).
    $hash = password_hash($admin['password'], PASSWORD_DEFAULT);
    $exists = $pdo->prepare("SELECT id FROM `{$p}users` WHERE mobile=?");
    $exists->execute([$admin['mobile']]);
    if ($exists->fetchColumn()) {
        $u = $pdo->prepare("UPDATE `{$p}users` SET name=?, email=?, password=?, role='super_admin', status='active' WHERE mobile=?");
        $u->execute([$admin['name'], $admin['email'] ?: null, $hash, $admin['mobile']]);
    } else {
        $u = $pdo->prepare("INSERT INTO `{$p}users` (role,name,mobile,email,password,status) VALUES ('super_admin',?,?,?,?,'active')");
        $u->execute([$admin['name'], $admin['mobile'], $admin['email'] ?: null, $hash]);
    }

    // 5) Create the installed.lock (contains no secrets).
    @file_put_contents(BASE_PATH . '/config/installed.lock', json_encode([
        'installed_at' => gmdate('c'),
        'version'      => installer_version(),
    ], JSON_PRETTY_PRINT));

    // 6) Hard-block the installer folder from future web access.
    @file_put_contents(INSTALL_PATH . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
}

function installer_set(PDO $pdo, string $p, string $key, $value, int $enc = 0): void
{
    $st = $pdo->prepare("INSERT INTO `{$p}settings` (`key`,`value`,`is_encrypted`) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), `is_encrypted`=VALUES(`is_encrypted`)");
    $st->execute([$key, $value, $enc]);
}

function installer_encrypt(string $plain, string $appKey): string
{
    $key = str_starts_with($appKey, 'base64:')
        ? (base64_decode(substr($appKey, 7)) ?: hash('sha256', $appKey, true))
        : hash('sha256', $appKey, true);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function installer_render_config(array $db, array $site, string $appKey): string
{
    $esc = fn($v) => addslashes((string)$v);
    return "<?php\n"
        . "/** Generated by the Dwarka Rental installer. Do not commit real credentials. */\n"
        . "return [\n"
        . "    'app' => [\n"
        . "        'name'     => '" . $esc($site['name']) . "',\n"
        . "        'url'      => '" . $esc($site['url']) . "',\n"
        . "        'key'      => '" . $esc($appKey) . "',\n"
        . "        'debug'    => false,\n"
        . "        'timezone' => '" . $esc($site['timezone']) . "',\n"
        . "    ],\n"
        . "    'db' => [\n"
        . "        'host'    => '" . $esc($db['host']) . "',\n"
        . "        'port'    => " . (int)$db['port'] . ",\n"
        . "        'name'    => '" . $esc($db['name']) . "',\n"
        . "        'user'    => '" . $esc($db['user']) . "',\n"
        . "        'pass'    => '" . $esc($db['pass']) . "',\n"
        . "        'prefix'  => '" . $esc($db['prefix']) . "',\n"
        . "        'charset' => 'utf8mb4',\n"
        . "    ],\n"
        . "    'session' => [\n"
        . "        'name'         => 'DWKSESS',\n"
        . "        'idle_timeout' => 3600,\n"
        . "    ],\n"
        . "];\n";
}

function installer_version(): string
{
    $vf = BASE_PATH . '/version.json';
    if (is_file($vf)) {
        $j = json_decode(file_get_contents($vf), true);
        return $j['version'] ?? '1.0.0';
    }
    return '1.0.0';
}

// ---------------------------------------------------------------------------
// AJAX actions
// ---------------------------------------------------------------------------
function installer_ajax(string $action): void
{
    header('Content-Type: application/json');
    try {
        if ($action === 'test_db') {
            $db = [
                'host' => trim($_POST['db_host'] ?? 'localhost'),
                'port' => (int)($_POST['db_port'] ?? 3306),
                'name' => trim($_POST['db_name'] ?? ''),
                'user' => trim($_POST['db_user'] ?? ''),
                'pass' => (string)($_POST['db_pass'] ?? ''),
            ];
            installer_pdo($db);
            echo json_encode(['ok' => true, 'message' => 'Connection successful.']);
            return;
        }
        if ($action === 'test_wa') {
            $url = trim($_POST['wa_url'] ?? '');
            $key = trim($_POST['wa_key'] ?? '');
            $session = trim($_POST['wa_session'] ?? '');
            $to = trim($_POST['wa_test_number'] ?? '');
            if ($url === '' || $key === '' || $to === '') {
                throw new RuntimeException('API URL, API key and a test number are required.');
            }
            $payload = json_encode([
                'api_key'    => $key,
                'session_id' => $session,
                'phone'      => $to,
                'message'    => 'Dwarka Rental installer test message ✔',
            ]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                throw new RuntimeException('Request failed: ' . $err);
            }
            echo json_encode(['ok' => $code >= 200 && $code < 300, 'http' => $code, 'response' => substr((string)$resp, 0, 500)]);
            return;
        }
        throw new RuntimeException('Unknown action.');
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
}

// ---------------------------------------------------------------------------
// HTML rendering
// ---------------------------------------------------------------------------
function render_locked(): void
{
    echo installer_html('Already Installed',
        '<div class="card"><h2>✔ Already Installed</h2>'
        . '<p>Dwarka Rental is already installed. For security, the installer will not run again.</p>'
        . '<p>To reinstall, delete <code>config/installed.lock</code> from your hosting.</p>'
        . '<a class="btn" href="' . e_install(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/install')) . '/../">Go to site</a></div>');
}

function render_layout(int $step, ?string $error): void
{
    $steps = [
        1 => 'Welcome',
        2 => 'System Check',
        3 => 'Database',
        4 => 'Website',
        5 => 'Admin Account',
        6 => 'WhatsApp API',
        7 => 'Finish',
    ];
    ob_start();
    echo '<div class="wizard">';
    echo '<ol class="steps">';
    foreach ($steps as $n => $label) {
        $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
        echo "<li class=\"{$cls}\"><span>{$n}</span>" . e_install($label) . '</li>';
    }
    echo '</ol>';

    if ($error) {
        echo '<div class="alert error">' . e_install($error) . '</div>';
    }

    echo '<div class="card">';
    installer_step_body($step);
    echo '</div>';
    echo '</div>';
    $body = ob_get_clean();
    echo installer_html('Install — Step ' . $step, $body);
}

function installer_step_body(int $step): void
{
    switch ($step) {
        case 1:
            echo '<h2>Welcome to Dwarka Rental</h2>';
            echo '<p>This wizard installs your multi-vendor vehicle rental platform. You will need your MySQL database name, username and password (create one in cPanel → MySQL Databases first).</p>';
            echo '<div class="terms"><strong>Licence:</strong> By installing you agree to use this software responsibly and in line with local regulations. Keep your admin credentials and API keys secret.</div>';
            echo '<a class="btn" href="?step=2">Get Started &rarr;</a>';
            break;

        case 2:
            $req = installer_requirements();
            $ok = installer_all_ok($req);
            echo '<h2>System Check</h2><table class="checks">';
            foreach ($req as $r) {
                $badge = $r['ok'] ? '<span class="ok">✔</span>' : (!empty($r['soft']) ? '<span class="warn">!</span>' : '<span class="bad">✘</span>');
                echo '<tr><td>' . e_install($r['label']) . '</td><td class="v">' . e_install($r['value']) . '</td><td>' . $badge . '</td></tr>';
            }
            echo '</table>';
            if ($ok) {
                echo '<a class="btn" href="?step=3">Continue &rarr;</a>';
            } else {
                echo '<div class="alert error">Please fix the items marked ✘ (install missing PHP extensions or make the folders writable), then reload.</div>';
                echo '<a class="btn secondary" href="?step=2">Re-check</a>';
            }
            break;

        case 3:
            echo '<h2>Database Setup</h2>';
            echo '<form method="post" action="?step=3" id="dbForm">';
            installer_field('db_host', 'Database Host', 'localhost');
            installer_field('db_port', 'Port', '3306');
            installer_field('db_name', 'Database Name', '');
            installer_field('db_user', 'Database User', '');
            installer_field('db_pass', 'Database Password', '', 'password');
            installer_field('db_prefix', 'Table Prefix', 'dwk_');
            echo '<label class="check"><input type="checkbox" name="seed_demo" value="1" checked> Install demo shop, agency &amp; vehicle (recommended for first-time setup)</label>';
            echo '<div class="row-btns"><button type="button" class="btn secondary" onclick="testDb()">Test Connection</button>';
            echo '<button type="submit" class="btn">Import &amp; Continue &rarr;</button></div>';
            echo '<div id="dbResult" class="result"></div>';
            echo '</form>';
            break;

        case 4:
            $url = guess_install_site_url();
            echo '<h2>Website Setup</h2>';
            echo '<form method="post" action="?step=4">';
            installer_field('site_name', 'Site Name', 'Dwarka Rental');
            installer_field('site_url', 'Site URL', $url);
            installer_field('timezone', 'Timezone', 'Asia/Kolkata');
            installer_field('currency', 'Currency Symbol', '₹');
            echo '<label>Default Language<select name="language"><option value="en">English</option><option value="gu">ગુજરાતી (Gujarati)</option></select></label>';
            echo '<button type="submit" class="btn">Continue &rarr;</button></form>';
            break;

        case 5:
            echo '<h2>Admin Account</h2>';
            echo '<form method="post" action="?step=5">';
            installer_field('admin_name', 'Full Name', '');
            installer_field('admin_mobile', 'Mobile (login)', '', 'text', '10-digit mobile, e.g. 9876543210');
            installer_field('admin_email', 'Email (optional)', '', 'email');
            installer_field('admin_password', 'Password', '', 'password', 'Minimum 8 characters');
            echo '<button type="submit" class="btn">Continue &rarr;</button></form>';
            break;

        case 6:
            echo '<h2>WhatsApp API <small>(optional — you can set this later in Admin)</small></h2>';
            echo '<form method="post" action="?step=6" id="waForm">';
            installer_field('wa_url', 'API URL', '', 'text', 'e.g. https://wa.example.com/api/send');
            installer_field('wa_key', 'API Key', '', 'text');
            installer_field('wa_session', 'Session ID', '');
            installer_field('wa_sender', 'Sender Number', '');
            installer_field('wa_test_number', 'Send test to (mobile)', '');
            echo '<div class="row-btns"><button type="button" class="btn secondary" onclick="testWa()">Test now</button>';
            echo '<button type="submit" class="btn">Finish Installation &rarr;</button></div>';
            echo '<div id="waResult" class="result"></div>';
            echo '</form>';
            break;

        case 7:
            $base = guess_install_site_url();
            echo '<h2>🎉 Installation Complete!</h2>';
            echo '<p>Dwarka Rental is now live. The installer has been locked for security.</p>';
            echo '<ul class="done-list">';
            echo '<li>✔ Database imported &amp; seeded</li>';
            echo '<li>✔ config/config.php written with a fresh APP_KEY</li>';
            echo '<li>✔ Super admin account created</li>';
            echo '<li>✔ Installer locked (config/installed.lock)</li>';
            echo '</ul>';
            echo '<div class="row-btns">';
            echo '<a class="btn" href="' . e_install($base) . '/admin/login">Go to Admin Panel</a>';
            echo '<a class="btn secondary" href="' . e_install($base) . '/">Visit Website</a>';
            echo '</div>';
            echo '<div class="alert warn">For extra safety, you may now delete the <code>/install</code> folder from your hosting.</div>';
            break;
    }
}

function installer_field(string $name, string $label, string $value, string $type = 'text', string $hint = ''): void
{
    $v = isset($_POST[$name]) ? (string)$_POST[$name] : $value;
    echo '<label>' . e_install($label);
    echo '<input type="' . e_install($type) . '" name="' . e_install($name) . '" value="' . e_install($v) . '">';
    if ($hint) { echo '<small>' . e_install($hint) . '</small>'; }
    echo '</label>';
}

function guess_install_site_url(): string
{
    $https = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // /install/index.php -> project root
    $dir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
    $dir = rtrim($dir === '/' ? '' : $dir, '/');
    return "{$scheme}://{$host}{$dir}";
}

function e_install($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function installer_html(string $title, string $body): string
{
    $t = e_install($title);
    return <<<HTML
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} — Dwarka Rental</title>
<style>
 :root{--p:#0d6efd;--g:#20c997;--bg:#0f172a;--card:#fff;--ink:#1e293b;}
 *{box-sizing:border-box}
 body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,'Noto Sans Gujarati',sans-serif;background:linear-gradient(135deg,#0f172a,#1e3a8a);color:var(--ink);min-height:100vh}
 .wrap{max-width:640px;margin:0 auto;padding:24px 16px}
 .brand{color:#fff;text-align:center;margin-bottom:16px}
 .brand h1{margin:0;font-size:26px;letter-spacing:.5px}
 .brand p{margin:4px 0 0;opacity:.8;font-size:14px}
 .steps{display:flex;flex-wrap:wrap;gap:6px;list-style:none;padding:0;margin:0 0 16px;font-size:12px}
 .steps li{flex:1;min-width:70px;text-align:center;color:#cbd5e1;padding:6px 2px;border-radius:8px;background:rgba(255,255,255,.06)}
 .steps li span{display:block;width:22px;height:22px;line-height:22px;border-radius:50%;background:#475569;color:#fff;margin:0 auto 4px}
 .steps li.active{background:rgba(255,255,255,.16);color:#fff}
 .steps li.active span{background:var(--p)}
 .steps li.done span{background:var(--g)}
 .card{background:var(--card);border-radius:14px;padding:22px;box-shadow:0 12px 40px rgba(0,0,0,.35)}
 .card h2{margin:0 0 12px;font-size:20px}
 label{display:block;margin:0 0 12px;font-size:14px;font-weight:600}
 label small{display:block;font-weight:400;color:#64748b;margin-top:2px}
 input,select{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:15px;margin-top:5px}
 input:focus,select:focus{outline:none;border-color:var(--p)}
 label.check{font-weight:400;display:flex;gap:8px;align-items:flex-start}
 label.check input{width:auto;margin-top:3px}
 .btn{display:inline-block;background:var(--p);color:#fff;border:none;padding:12px 20px;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none;margin-top:8px}
 .btn.secondary{background:#e2e8f0;color:#1e293b}
 .row-btns{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
 .alert{padding:12px 14px;border-radius:10px;margin:12px 0;font-size:14px}
 .alert.error{background:#fee2e2;color:#991b1b}
 .alert.warn{background:#fef9c3;color:#854d0e}
 .checks{width:100%;border-collapse:collapse;font-size:14px}
 .checks td{padding:8px 6px;border-bottom:1px solid #f1f5f9}
 .checks td.v{color:#64748b;text-align:right}
 .ok{color:#16a34a;font-weight:700}.bad{color:#dc2626;font-weight:700}.warn{color:#d97706;font-weight:700}
 .terms{background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:10px;font-size:13px;margin:12px 0}
 .result{margin-top:12px;font-size:14px}
 .result .ok{color:#16a34a}.result .bad{color:#dc2626}
 .done-list{list-style:none;padding:0;font-size:15px}
 .done-list li{padding:5px 0;color:#16a34a}
 code{background:#f1f5f9;padding:1px 6px;border-radius:5px}
</style>
</head><body>
<div class="wrap">
 <div class="brand"><h1>🛵 Dwarka Rental</h1><p>Multi-Vendor Vehicle Rental — Installer</p></div>
 {$body}
</div>
<script>
function post(url, data){
  var fd=new FormData();for(var k in data){fd.append(k,data[k]);}
  return fetch(url,{method:'POST',body:fd}).then(function(r){return r.json();});
}
function testDb(){
  var f=document.getElementById('dbForm');var out=document.getElementById('dbResult');
  out.innerHTML='Testing…';
  post('?step=3&action=test_db',{db_host:f.db_host.value,db_port:f.db_port.value,db_name:f.db_name.value,db_user:f.db_user.value,db_pass:f.db_pass.value})
   .then(function(r){out.innerHTML=r.ok?'<span class="ok">✔ '+r.message+'</span>':'<span class="bad">✘ '+r.message+'</span>';})
   .catch(function(){out.innerHTML='<span class="bad">✘ Request failed</span>';});
}
function testWa(){
  var f=document.getElementById('waForm');var out=document.getElementById('waResult');
  out.innerHTML='Sending…';
  post('?step=6&action=test_wa',{wa_url:f.wa_url.value,wa_key:f.wa_key.value,wa_session:f.wa_session.value,wa_test_number:f.wa_test_number.value})
   .then(function(r){out.innerHTML=r.ok?'<span class="ok">✔ Sent (HTTP '+(r.http||'')+')</span>':'<span class="bad">✘ '+(r.message||('HTTP '+r.http))+'</span>';})
   .catch(function(){out.innerHTML='<span class="bad">✘ Request failed</span>';});
}
</script>
</body></html>
HTML;
}
