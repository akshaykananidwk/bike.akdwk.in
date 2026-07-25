<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Request;

/**
 * Handles /s/{code} — the shop QR landing. Stores the referral in the session
 * and a 30-day cookie, logs the scan, then redirects to the listing.
 */
class ReferralController extends Controller
{
    public function scan(array $params): string
    {
        $code = trim($params['code'] ?? '');
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE code=? AND status='active'", [$code]);

        if ($shop) {
            // Attribution window (days) from settings.
            $days = max(1, (int)setting('attribution_days', 30));
            Session::set('ref_shop_id', (int)$shop['id']);
            Session::set('ref_shop_name', $shop['name']);
            setcookie('ref_shop', $shop['code'], [
                'expires'  => time() + $days * 86400,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            // Log the scan for analytics.
            Database::insert('shop_scans', [
                'shop_id'    => (int)$shop['id'],
                'ip'         => Request::ip(),
                'user_agent' => Request::userAgent(),
                'created_at' => now(),
            ]);

            return $this->redirect('/vehicles');
        }

        // Invalid/inactive code — behave like the normal homepage.
        return $this->redirect('/');
    }
}
