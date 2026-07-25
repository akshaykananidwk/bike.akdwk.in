<?php
namespace App\Controllers\Shop;

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
        if (Auth::check() && Auth::is('shop')) {
            return $this->redirect('/shop');
        }
        if (setting('shop_registration_enabled', '1') !== '1') {
            Session::flash('error', 'Shop registration is currently closed. Please contact us.');
            return $this->redirect('/shop/login');
        }

        $error = null;
        if (Request::isPost()) {
            $this->verifyCsrf();
            $res = PartnerRegistration::register('shop', Request::all());
            if ($res['ok']) {
                ActivityLog::record('shop.self_register', 'shop', $res['id']);
                // Log the new partner straight in so they can complete their profile.
                if (!empty($res['user_id'])) {
                    $user = Database::fetch("SELECT * FROM {p}users WHERE id=?", [$res['user_id']]);
                    if ($user) { Auth::login($user); }
                }
                Session::flash('success', $res['approved']
                    ? 'Welcome! Your shop is active. Your code is ' . $res['code'] . '.'
                    : 'Registration received! Your shop code is ' . $res['code'] . '. Our team will approve your account shortly.');
                return $this->redirect('/shop');
            }
            $error = $res['error'];
            Session::flash('old', Request::all());
        }

        return $this->view('shop/register', [
            'title' => 'Shop Partner Registration',
            'error' => $error,
        ], null);
    }
}
