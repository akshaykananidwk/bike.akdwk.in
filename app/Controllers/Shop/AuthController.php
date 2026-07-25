<?php
namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;

class AuthController extends Controller
{
    public function login(): string
    {
        if (Auth::check() && Auth::is('shop')) { return $this->redirect('/shop'); }
        $error = null;
        if (Request::isPost()) {
            $this->verifyCsrf();
            $r = Auth::attempt(Request::post('login', ''), Request::post('password', ''));
            if ($r['ok'] && $r['user']['role'] === 'shop') {
                return $this->redirect('/shop');
            }
            if ($r['ok']) { Auth::logout(); $error = 'This account is not a shop partner account.'; }
            else { $error = $r['error']; }
        }
        return $this->view('shop/login', ['title' => 'Shop Partner Login', 'error' => $error, 'panel' => 'shop'], null);
    }

    public function logout(): string
    {
        Auth::logout();
        Session::flash('success', 'Logged out.');
        return $this->redirect('/shop/login');
    }
}
