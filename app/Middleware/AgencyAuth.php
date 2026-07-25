<?php
namespace App\Middleware;

use App\Core\Auth;

/** Guards the /agency panel. */
class AgencyAuth
{
    public function handle(): void
    {
        Auth::require('/agency/login', 'agency');
    }
}
