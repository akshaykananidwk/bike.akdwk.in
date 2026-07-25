<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\ActivityLog;
use App\Services\DbBackup;

class BackupController extends Controller
{
    public function download(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }
        ActivityLog::record('backup.download', 'database');
        $sql = DbBackup::dump();
        $name = 'dwarka-backup-' . date('Ymd_His') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        return '';
    }
}
