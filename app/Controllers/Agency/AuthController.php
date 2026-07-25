<?php
namespace App\Controllers\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;

class AuthController extends Controller
{
    public function login(): string
    {
        if (Auth::check() && Auth::is('agency')) { return $this->redirect('/agency'); }
        $error = null;
        if (Request::isPost()) {
            $this->verifyCsrf();
            $r = Auth::attempt(Request::post('login', ''), Request::post('password', ''));
            if ($r['ok'] && $r['user']['role'] === 'agency') { return $this->redirect('/agency'); }
            if ($r['ok']) { Auth::logout(); $error = 'This account is not an agency account.'; }
            else { $error = $r['error']; }
        }
        return $this->view('agency/login', ['title' => 'Agency Login', 'error' => $error], null);
    }

    public function logout(): string
    {
        Auth::logout();
        Session::flash('success', 'Logged out.');
        return $this->redirect('/agency/login');
    }
}
