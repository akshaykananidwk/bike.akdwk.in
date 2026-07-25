<?php
namespace App\Middleware;

use App\Core\Auth;

/** Guards the /shop partner panel. */
class ShopAuth
{
    public function handle(): void
    {
        Auth::require('/shop/login', 'shop');
    }
}
