<?php
namespace App\Controllers\Shop;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\WalletService;
use App\Services\PayoutService;
use App\Services\Whatsapp;

class WalletController extends Controller
{
    private function shopId(): int
    {
        return (int)(Auth::user()['shop_id'] ?? 0);
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
        $id = $this->shopId();
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE owner_type='shop' AND owner_id=?", [$id]) ?: ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];
        $txns = Database::fetchAll(
            "SELECT wt.* FROM {p}wallet_transactions wt JOIN {p}wallets w ON w.id=wt.wallet_id
             WHERE w.owner_type='shop' AND w.owner_id=? ORDER BY wt.id DESC LIMIT 50", [$id]
        );
        $payouts = Database::fetchAll("SELECT * FROM {p}payout_requests WHERE owner_type='shop' AND owner_id=? ORDER BY id DESC LIMIT 20", [$id]);
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$id]);
        return $this->view('shop/wallet', [
            'title' => 'Wallet', 'base' => '/shop', 'nav' => $this->nav('wallet'),
            'wallet' => $wallet, 'txns' => $txns, 'payouts' => $payouts, 'shop' => $shop,
            'minWithdrawal' => (float)setting('min_withdrawal', 500),
        ], 'panel');
    }

    public function withdraw(): string
    {
        $this->verifyCsrf();
        $id = $this->shopId();
        try {
            $amount = (float)Request::post('amount', 0);
            $payoutId = PayoutService::request('shop', $id, $amount);
            $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$id]);
            if ($adminWa = setting('contact_whatsapp', '')) {
                Whatsapp::queue($adminWa, "New withdrawal request #{$payoutId} from shop {$shop['name']} for " . money($amount) . ".");
            }
            Session::flash('success', 'Withdrawal request submitted.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/shop/wallet');
    }

    public function bank(): string
    {
        $this->verifyCsrf();
        $id = $this->shopId();
        Database::update('shops', [
            'bank_holder'  => Request::post('bank_holder') ?: null,
            'bank_account' => Request::post('bank_account') ?: null,
            'bank_ifsc'    => strtoupper((string)Request::post('bank_ifsc')) ?: null,
            'bank_name'    => Request::post('bank_name') ?: null,
            'upi_id'       => Request::post('upi_id') ?: null,
        ], ['id' => $id]);
        Session::flash('success', 'Bank details saved.');
        return $this->redirect('/shop/wallet');
    }

    public function statement(): string
    {
        $id = $this->shopId();
        $shop = Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$id]);
        $rows = Database::fetchAll(
            "SELECT cl.*, b.code FROM {p}commission_ledger cl JOIN {p}bookings b ON b.id=cl.booking_id
             WHERE cl.beneficiary_type='shop' AND cl.beneficiary_id=? ORDER BY cl.id DESC", [$id]
        );
        if (class_exists(\App\Services\StatementPdf::class)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="statement-' . $shop['code'] . '.pdf"');
            echo \App\Services\StatementPdf::shop($shop, $rows);
            return '';
        }
        // Fallback: CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="statement-' . $shop['code'] . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Booking', 'Type', 'Amount', 'Note']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['created_at'], $r['code'], $r['entry_type'], $r['amount'], $r['note']]);
        }
        fclose($out);
        return '';
    }
}
