<?php
namespace App\Controllers\Agency;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\PayoutService;
use App\Services\Whatsapp;

class WalletController extends Controller
{
    private function agencyId(): int { return (int)(Auth::user()['agency_id'] ?? 0); }

    public function index(): string
    {
        $id = $this->agencyId();
        $wallet = Database::fetch("SELECT * FROM {p}wallets WHERE owner_type='agency' AND owner_id=?", [$id]) ?: ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0];
        $txns = Database::fetchAll("SELECT wt.* FROM {p}wallet_transactions wt JOIN {p}wallets w ON w.id=wt.wallet_id WHERE w.owner_type='agency' AND w.owner_id=? ORDER BY wt.id DESC LIMIT 50", [$id]);
        $payouts = Database::fetchAll("SELECT * FROM {p}payout_requests WHERE owner_type='agency' AND owner_id=? ORDER BY id DESC LIMIT 20", [$id]);
        $agency = Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$id]);
        return $this->view('agency/wallet', [
            'title' => 'Wallet', 'base' => '/agency', 'nav' => DashboardController::nav('wallet'),
            'wallet' => $wallet, 'txns' => $txns, 'payouts' => $payouts, 'agency' => $agency,
            'minWithdrawal' => (float)setting('min_withdrawal', 500),
        ], 'panel');
    }

    public function withdraw(): string
    {
        $this->verifyCsrf();
        $id = $this->agencyId();
        // Save bank details if submitted alongside.
        if (Request::post('save_bank')) {
            Database::update('agencies', [
                'bank_holder'  => Request::post('bank_holder') ?: null,
                'bank_account' => Request::post('bank_account') ?: null,
                'bank_ifsc'    => strtoupper((string)Request::post('bank_ifsc')) ?: null,
                'bank_name'    => Request::post('bank_name') ?: null,
            ], ['id' => $id]);
            Session::flash('success', 'Bank details saved.');
            return $this->redirect('/agency/wallet');
        }
        try {
            $amount = (float)Request::post('amount', 0);
            $pid = PayoutService::request('agency', $id, $amount);
            $agency = Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$id]);
            if ($adminWa = setting('contact_whatsapp', '')) {
                Whatsapp::queue($adminWa, "New withdrawal request #{$pid} from agency {$agency['name']} for " . money($amount) . ".");
            }
            Session::flash('success', 'Withdrawal request submitted.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/agency/wallet');
    }
}
