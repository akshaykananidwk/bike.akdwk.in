<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Uploader;
use App\Core\ActivityLog;
use App\Services\PayoutService;
use App\Services\Whatsapp;

class PayoutController extends Controller
{
    public function index(): string
    {
        $status = Request::query('status', 'pending');
        $where = '1'; $params = [];
        if (in_array($status, ['pending','approved','rejected','paid'], true)) { $where = 'pr.status=?'; $params = [$status]; }

        $payouts = Database::fetchAll(
            "SELECT pr.*,
                    CASE WHEN pr.owner_type='shop' THEN (SELECT name FROM {p}shops WHERE id=pr.owner_id)
                         ELSE (SELECT name FROM {p}agencies WHERE id=pr.owner_id) END AS owner_name,
                    (SELECT balance FROM {p}wallets w WHERE w.owner_type=pr.owner_type AND w.owner_id=pr.owner_id) AS balance
             FROM {p}payout_requests pr WHERE {$where} ORDER BY pr.id DESC LIMIT 300",
            $params
        );
        return $this->view('admin/payouts/index', [
            'title' => 'Payouts', 'active' => 'payouts', 'payouts' => $payouts, 'status' => $status,
        ], 'admin');
    }

    public function process(array $p): string
    {
        $this->verifyCsrf();
        $payout = Database::fetch("SELECT * FROM {p}payout_requests WHERE id=?", [$p['id']]);
        if (!$payout) { Session::flash('error', 'Payout not found.'); return $this->redirect('/admin/payouts'); }
        $action = Request::post('action');
        $adminId = (int)Auth::id();

        try {
            if ($action === 'approve') {
                PayoutService::approve((int)$payout['id'], $adminId);
                $this->notify($payout, 'payout_approved', money($payout['amount']));
                Session::flash('success', 'Payout approved.');
            } elseif ($action === 'reject') {
                PayoutService::reject((int)$payout['id'], $adminId, (string)Request::post('note'));
                Session::flash('success', 'Payout rejected.');
            } elseif ($action === 'paid') {
                $proof = null;
                $file = Request::file('proof_image');
                if ($file && ($file['error'] ?? 1) === UPLOAD_ERR_OK) {
                    $r = Uploader::handle($file, 'payments', ['jpg','jpeg','png','webp','pdf']);
                    if ($r['ok']) { $proof = $r['path']; }
                }
                PayoutService::markPaid((int)$payout['id'], $adminId, (string)Request::post('utr'), $proof);
                $this->notify($payout, 'payout_paid', money($payout['amount']), (string)Request::post('utr'));
                Session::flash('success', 'Payout marked as paid and wallet debited.');
            }
            ActivityLog::record('payout.' . $action, 'payout', $payout['id']);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        return $this->redirect('/admin/payouts');
    }

    private function notify(array $payout, string $template, string $amount, string $utr = ''): void
    {
        $table = $payout['owner_type'] === 'shop' ? 'shops' : 'agencies';
        $owner = Database::fetch("SELECT whatsapp, mobile FROM {p}{$table} WHERE id=?", [$payout['owner_id']]);
        $to = $owner['whatsapp'] ?? $owner['mobile'] ?? '';
        if ($to) {
            Whatsapp::notify($template, $to, ['amount' => $amount, 'balance' => $utr]);
        }
    }
}
