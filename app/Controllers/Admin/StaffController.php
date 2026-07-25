<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\ActivityLog;

class StaffController extends Controller
{
    /** Available granular permissions for sub-admins. */
    public const PERMISSIONS = [
        'bookings' => 'Manage bookings',
        'payments' => 'Verify payments',
        'payouts'  => 'Approve payouts',
        'shops'    => 'Manage shops',
        'agencies' => 'Manage agencies',
        'vehicles' => 'Manage vehicles',
        'reports'  => 'View reports',
        'whatsapp' => 'WhatsApp centre',
    ];

    public function index(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }

        if (Request::isPost()) {
            $this->verifyCsrf();
            $action = Request::post('action');
            if ($action === 'delete') {
                $id = (int)Request::post('id');
                if ($id !== (int)Auth::id()) {
                    Database::run("DELETE FROM {p}users WHERE id=? AND role='staff'", [$id]);
                    Session::flash('success', 'Staff removed.');
                }
                return $this->redirect('/admin/staff');
            }
            $perms = array_values(array_intersect(array_keys(self::PERMISSIONS), (array)Request::post('permissions', [])));
            $fields = [
                'name'        => trim((string)Request::post('name')),
                'mobile'      => preg_replace('/[^0-9]/', '', (string)Request::post('mobile')),
                'email'       => Request::post('email') ?: null,
                'permissions' => json_encode($perms),
                'status'      => Request::post('status', 'active'),
            ];
            if ($fields['name'] === '' || !preg_match('/^[6-9]\d{9}$/', $fields['mobile'])) {
                Session::flash('error', 'Valid name and mobile required.');
                return $this->redirect('/admin/staff');
            }
            $id = (int)Request::post('id');
            $pass = Request::post('password');
            if ($id) {
                if ($pass && strlen($pass) >= 8) { $fields['password'] = password_hash($pass, PASSWORD_DEFAULT); }
                Database::update('users', $fields, ['id' => $id]);
                Session::flash('success', 'Staff updated.');
            } else {
                if (!$pass || strlen($pass) < 8) { Session::flash('error', 'Password (min 8) required for new staff.'); return $this->redirect('/admin/staff'); }
                if (Database::scalar("SELECT COUNT(*) FROM {p}users WHERE mobile=?", [$fields['mobile']])) {
                    Session::flash('error', 'A user with this mobile already exists.');
                    return $this->redirect('/admin/staff');
                }
                $fields['role'] = 'staff';
                $fields['password'] = password_hash($pass, PASSWORD_DEFAULT);
                $newId = Database::insert('users', $fields);
                ActivityLog::record('staff.create', 'user', $newId);
                Session::flash('success', 'Staff created.');
            }
            return $this->redirect('/admin/staff');
        }

        $staff = Database::fetchAll("SELECT * FROM {p}users WHERE role IN ('staff','super_admin') ORDER BY id");
        return $this->view('admin/staff/index', [
            'title' => 'Staff & Roles', 'active' => 'staff', 'staff' => $staff, 'permissions' => self::PERMISSIONS,
        ], 'admin');
    }
}
