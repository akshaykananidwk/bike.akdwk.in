<?php
namespace App\Controllers\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;

class DashboardController extends Controller
{
    private function agency(): ?array
    {
        $u = Auth::user();
        return $u && $u['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$u['agency_id']]) : null;
    }

    public static function nav(string $active): array
    {
        $mk = fn($k, $url, $icon, $label) => ['url' => $url, 'icon' => $icon, 'label' => $label, 'active' => $active === $k];
        return [
            $mk('home', '/agency', 'bi-house', 'Home'),
            $mk('vehicles', '/agency/vehicles', 'bi-scooter', 'Vehicles'),
            $mk('bookings', '/agency/bookings', 'bi-calendar-check', 'Bookings'),
            $mk('wallet', '/agency/wallet', 'bi-wallet2', 'Wallet'),
            $mk('profile', '/agency/profile', 'bi-person', 'Profile'),
        ];
    }

    public function index(): string
    {
        $a = $this->agency();
        if (!$a) { Auth::logout(); return $this->redirect('/agency/login'); }
        $id = (int)$a['id'];
        $stats = [
            'vehicles'      => (int)Database::scalar("SELECT COUNT(*) FROM {p}vehicles WHERE agency_id=?", [$id]),
            'today'         => (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE agency_id=? AND DATE(created_at)=CURDATE()", [$id]),
            'active'        => (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE agency_id=? AND status IN ('confirmed','picked_up')", [$id]),
            'wallet'        => (float)Database::scalar("SELECT balance FROM {p}wallets WHERE owner_type='agency' AND owner_id=?", [$id]),
        ];
        $upcoming = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name FROM {p}bookings b LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             WHERE b.agency_id=? AND b.status IN ('confirmed','picked_up') ORDER BY b.pickup_at ASC LIMIT 10", [$id]
        );
        return $this->view('agency/dashboard', [
            'title' => $a['name'], 'base' => '/agency', 'nav' => self::nav('home'),
            'agency' => $a, 'stats' => $stats, 'upcoming' => $upcoming,
        ], 'panel');
    }

    public function bookings(): string
    {
        $a = $this->agency();
        if (!$a) { return $this->redirect('/agency/login'); }
        $bookings = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name FROM {p}bookings b LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             WHERE b.agency_id=? ORDER BY b.id DESC LIMIT 100", [$a['id']]
        );
        return $this->view('agency/bookings', [
            'title' => 'Bookings', 'base' => '/agency', 'nav' => self::nav('bookings'), 'bookings' => $bookings,
        ], 'panel');
    }

    public function pickup(array $p): string
    {
        $this->verifyCsrf();
        $a = $this->agency();
        $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=? AND agency_id=?", [$p['id'], $a['id'] ?? 0]);
        if ($booking && $booking['status'] === 'confirmed') {
            // Verify the pickup OTP the customer shows at handover.
            $otp = preg_replace('/[^0-9]/', '', (string)Request::post('pickup_otp'));
            if (!empty($booking['pickup_otp']) && !hash_equals((string)$booking['pickup_otp'], (string)$otp)) {
                Session::flash('error', 'Incorrect pickup OTP. Ask the customer for the 6-digit OTP on their booking screen.');
                return $this->redirect('/agency/bookings');
            }
            $update = [
                'status'          => 'picked_up',
                'picked_up_at'    => now(),
                'pickup_verified' => 1,
                'pickup_odo'      => Request::post('odo') ?: null,
                'pickup_fuel'     => Request::post('fuel') ?: null,
            ];
            $photo = Request::file('photo');
            if ($photo && ($photo['error'] ?? 1) === UPLOAD_ERR_OK) {
                $r = Uploader::handle($photo, 'bookings', ['jpg','jpeg','png','webp']);
                if ($r['ok']) { $update['pickup_photo'] = $r['path']; }
            }
            Database::update('bookings', $update, ['id' => $booking['id']]);
            Session::flash('success', 'Marked as picked up.');
        }
        return $this->redirect('/agency/bookings');
    }

    public function markReturn(array $p): string
    {
        $this->verifyCsrf();
        $a = $this->agency();
        $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=? AND agency_id=?", [$p['id'], $a['id'] ?? 0]);
        if ($booking && $booking['status'] === 'picked_up') {
            $extra = (float)Request::post('extra_charges', 0);
            $update = [
                'status'        => 'returned',
                'returned_at'   => now(),
                'return_odo'    => Request::post('odo') ?: null,
                'return_fuel'   => Request::post('fuel') ?: null,
                'extra_charges' => $extra,
                'total_amount'  => round((float)$booking['total_amount'] + $extra, 2),
                'balance_amount'=> round((float)$booking['balance_amount'] + $extra, 2),
            ];
            $photo = Request::file('photo');
            if ($photo && ($photo['error'] ?? 1) === UPLOAD_ERR_OK) {
                $r = Uploader::handle($photo, 'bookings', ['jpg','jpeg','png','webp']);
                if ($r['ok']) { $update['return_photo'] = $r['path']; }
            }
            Database::update('bookings', $update, ['id' => $booking['id']]);
            Session::flash('success', 'Marked as returned. Deposit refund = ' . money(max(0, (float)$booking['deposit'] - $extra)) . '.');
        }
        return $this->redirect('/agency/bookings');
    }

    public function profile(): string
    {
        $a = $this->agency();
        if (!$a) { return $this->redirect('/agency/login'); }
        $u = Auth::user();
        if (Request::isPost()) {
            $this->verifyCsrf();
            // Password change
            if ($new = Request::post('password')) {
                if (strlen($new) >= 8) {
                    Database::update('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], ['id' => $u['id']]);
                    Session::flash('success', 'Password updated.');
                } else { Session::flash('error', 'Password too short.'); }
                return $this->redirect('/agency/profile');
            }
            // UPI QR upload
            $qr = Request::file('upi_qr_image');
            $fields = ['upi_id' => Request::post('upi_id') ?: null];
            if ($qr && ($qr['error'] ?? 1) === UPLOAD_ERR_OK) {
                $r = Uploader::handle($qr, 'agencies', ['jpg','jpeg','png','webp']);
                if ($r['ok']) { $fields['upi_qr_image'] = $r['path']; }
            }
            Database::update('agencies', $fields, ['id' => $a['id']]);
            Session::flash('success', 'Profile updated.');
            return $this->redirect('/agency/profile');
        }
        return $this->view('agency/profile', ['title' => 'Profile', 'base' => '/agency', 'nav' => self::nav('profile'), 'agency' => $a, 'user' => $u], 'panel');
    }
}
