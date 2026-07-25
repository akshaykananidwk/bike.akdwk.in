<?php
namespace App\Controllers\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\ActivityLog;
use App\Services\PartnerRegistration;

class RegisterController extends Controller
{
    public function register(): string
    {
        if (Auth::check() && Auth::is('agency')) {
            return $this->redirect('/agency');
        }
        if (setting('agency_registration_enabled', '1') !== '1') {
            Session::flash('error', 'Agency registration is currently closed. Please contact us.');
            return $this->redirect('/agency/login');
        }

        $error = null;
        if (Request::isPost()) {
            $this->verifyCsrf();
            $res = PartnerRegistration::register('agency', Request::all());
            if ($res['ok']) {
                ActivityLog::record('agency.self_register', 'agency', $res['id']);
                if (!empty($res['user_id'])) {
                    $user = Database::fetch("SELECT * FROM {p}users WHERE id=?", [$res['user_id']]);
                    if ($user) { Auth::login($user); }
                }
                Session::flash('success', $res['approved']
                    ? 'Welcome aboard! Your agency is active. Your code is ' . $res['code'] . '.'
                    : 'Registration received! Your agency code is ' . $res['code'] . '. Our team will approve your account shortly.');
                return $this->redirect('/agency');
            }
            $error = $res['error'];
            Session::flash('old', Request::all());
        }

        return $this->view('agency/register', [
            'title' => 'Agency Registration',
            'error' => $error,
        ], null);
    }
}
