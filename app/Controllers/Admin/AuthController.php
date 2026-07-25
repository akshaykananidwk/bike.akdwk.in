<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;

class AuthController extends Controller
{
    public function login(): string
    {
        if (Auth::check() && Auth::is('super_admin', 'staff')) {
            return $this->redirect('/admin');
        }

        $error = null;
        if (Request::isPost()) {
            $this->verifyCsrf();
            $result = Auth::attempt(Request::post('login', ''), Request::post('password', ''));
            if ($result['ok'] && in_array($result['user']['role'], ['super_admin', 'staff'], true)) {
                ActivityLog::record('login', 'user', $result['user']['id']);
                return $this->redirect('/admin');
            }
            // If a non-admin authenticated, deny admin access.
            if ($result['ok']) {
                Auth::logout();
                $error = 'This account cannot access the admin panel.';
            } else {
                $error = $result['error'];
            }
        }

        return $this->view('admin/login', [
            'title' => 'Admin Login',
            'error' => $error,
        ]);
    }

    public function logout(): string
    {
        ActivityLog::record('logout', 'user', Auth::id());
        Auth::logout();
        Session::flash('success', 'You have been logged out.');
        return $this->redirect('/admin/login');
    }
}
