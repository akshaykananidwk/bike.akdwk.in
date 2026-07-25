<?php
/**
 * Sample configuration. The installer writes the real config/config.php with
 * your database credentials and a generated APP_KEY. Do NOT edit this sample —
 * it exists only as a reference and is never loaded by the app.
 */
return [
    'app' => [
        'name'     => 'Dwarka Rental',
        'url'      => 'https://your-domain.com',
        'key'      => 'base64:REPLACED_ON_INSTALL',
        'debug'    => false,
        'timezone' => 'Asia/Kolkata',
    ],
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'dwarka_rental',
        'user'    => 'db_user',
        'pass'    => 'db_pass',
        'prefix'  => 'dwk_',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'         => 'DWKSESS',
        'idle_timeout' => 3600,
    ],
];
