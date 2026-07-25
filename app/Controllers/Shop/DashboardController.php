<?php
namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\WalletService;
use App\Services\BookingService;

class DashboardController extends Controller
{
    private function shop(): ?array
    {
        $u = Auth::user();
        return $u && $u['shop_id'] ? Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$u['shop_id']]) : null;
    }

    private function nav(string $active): array
    {
        $mk = fn($k, $url, $icon, $label) => ['url' => $url, 'icon' => $icon, 'label' => $label, 'active' => $active === $k];
        return [
            $mk('home', '/shop', 'bi-house', 'Home'),
            $mk('bookings', '/shop/bookings', 'bi-calendar-check', 'Bookings'),
            $mk('qr', '/shop/qr', 'bi-qr-code', 'My QR'),
            $mk('wallet', '/shop/wallet', 'bi-wallet2', 'Wallet'),
            $mk('profile', '/shop/profile', 'bi-person', 'Profile'),
        ];
    }

    public function index(): string
    {
        $shop = $this->shop();
        if (!$shop) { Auth::logout(); return $this->redirect('/shop/login'); }
        $id = (int)$shop['id'];

        [$todayC, $totalC] = BookingService::shopCommissionTotals($id);
        $monthC = (float)Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0)
             FROM {p}commission_ledger WHERE beneficiary_type='shop' AND beneficiary_id=? AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())",
            [$id]
        );
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE owner_type='shop' AND owner_id=?", [$id]) ?: ['balance' => 0, 'total_withdrawn' => 0];
        $todayBookings = (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE shop_id=? AND DATE(created_at)=CURDATE()", [$id]);

        return $this->view('shop/dashboard', [
            'title' => $shop['name'], 'base' => '/shop', 'nav' => $this->nav('home'),
            'shop' => $shop, 'todayC' => $todayC, 'totalC' => $totalC, 'monthC' => $monthC,
            'wallet' => $wallet, 'todayBookings' => $todayBookings,
        ], 'panel');
    }

    public function bookings(): string
    {
        $shop = $this->shop();
        if (!$shop) { return $this->redirect('/shop/login'); }
        $bookings = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name,
                    (SELECT amount FROM {p}commission_ledger cl WHERE cl.booking_id=b.id AND cl.beneficiary_type='shop' AND cl.entry_type='credit' LIMIT 1) AS commission
             FROM {p}bookings b LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             WHERE b.shop_id=? ORDER BY b.id DESC LIMIT 100",
            [$shop['id']]
        );
        return $this->view('shop/bookings', [
            'title' => 'Bookings', 'base' => '/shop', 'nav' => $this->nav('bookings'), 'bookings' => $bookings,
        ], 'panel');
    }

    public function qr(): string
    {
        $shop = $this->shop();
        if (!$shop) { return $this->redirect('/shop/login'); }
        $url = rtrim(setting('site_url', '') ?: guess_base_url(), '/') . '/s/' . $shop['code'];
        return $this->view('shop/qr', [
            'title' => 'My QR / Poster', 'base' => '/shop', 'nav' => $this->nav('qr'),
            'shop' => $shop, 'qrData' => \App\Core\Qr::toDataUri($url, 8, 2), 'shareUrl' => $url,
        ], 'panel');
    }

    /** Shop-scoped poster download (own shop only). */
    public function poster(): string
    {
        $shop = $this->shop();
        if (!$shop) { return $this->redirect('/shop/login'); }
        $format = Request::query('format', 'pdf');
        $size = strtoupper((string)Request::query('size', 'A4')) === 'A5' ? 'A5' : 'A4';
        $bg = $shop['poster_bg'] ? BASE_PATH . '/uploads/' . $shop['poster_bg'] : null;
        $gen = new \App\Services\PosterGenerator();
        if ($format === 'png') {
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="poster-' . $shop['code'] . '.png"');
            echo $gen->png($shop, $bg);
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="poster-' . $shop['code'] . '-' . $size . '.pdf"');
            echo $gen->pdf($shop, $size, $bg);
        }
        return '';
    }

    public function profile(): string
    {
        $shop = $this->shop();
        if (!$shop) { return $this->redirect('/shop/login'); }
        $u = Auth::user();
        if (Request::isPost()) {
            $this->verifyCsrf();
            $new = Request::post('password');
            if ($new && strlen($new) >= 8) {
                Database::update('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], ['id' => $u['id']]);
                Session::flash('success', 'Password updated.');
            } else {
                Session::flash('error', 'Password must be at least 8 characters.');
            }
            return $this->redirect('/shop/profile');
        }
        return $this->view('shop/profile', [
            'title' => 'Profile', 'base' => '/shop', 'nav' => $this->nav('profile'), 'shop' => $shop, 'user' => $u,
        ], 'panel');
    }
}
