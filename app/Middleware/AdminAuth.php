<?php
namespace App\Middleware;

use App\Core\Auth;

/** Guards the /admin area — super_admin or staff roles only. */
class AdminAuth
{
    public function handle(): void
    {
        Auth::require('/admin/login', 'super_admin', 'staff');
    }
}
