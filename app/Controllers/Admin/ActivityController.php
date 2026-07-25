<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class ActivityController extends Controller
{
    public function index(): string
    {
        $logs = Database::fetchAll(
            "SELECT al.*, u.name AS user_name FROM {p}activity_log al
             LEFT JOIN {p}users u ON u.id=al.user_id ORDER BY al.id DESC LIMIT 300"
        );
        return $this->view('admin/activity/index', ['title' => 'Activity Log', 'active' => 'activity', 'logs' => $logs], 'admin');
    }
}
