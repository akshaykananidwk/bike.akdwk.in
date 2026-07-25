<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\ActivityLog;
use App\Services\Payments\PaymentProcessor;
use App\Services\Whatsapp;

class PaymentController extends Controller
{
    public function index(): string
    {
        $status = Request::query('status', '');
        $where = '1'; $params = [];
        if (in_array($status, ['created','pending_verification','paid','failed','refunded'], true)) {
            $where = 'p.status=?'; $params = [$status];
        }
        $payments = Database::fetchAll(
            "SELECT p.*, b.code AS booking_code, b.customer_name, b.customer_mobile
             FROM {p}payments p LEFT JOIN {p}bookings b ON b.id=p.booking_id
             WHERE {$where} ORDER BY p.id DESC LIMIT 300",
            $params
        );
        return $this->view('admin/payments/index', [
            'title' => 'Payments', 'active' => 'payments', 'payments' => $payments, 'status' => $status,
        ], 'admin');
    }

    /** Approve or reject a UPI (pending_verification) payment. */
    public function verify(array $p): string
    {
        $this->verifyCsrf();
        $payment = Database::fetch("SELECT * FROM {p}payments WHERE id=?", [$p['id']]);
        if (!$payment) { Session::flash('error', 'Payment not found.'); return $this->redirect('/admin/payments'); }

        $action = Request::post('action');
        if ($action === 'approve') {
            PaymentProcessor::approve((int)$payment['id'], (int)Auth::id());
            ActivityLog::record('payment.approve', 'payment', $payment['id']);
            $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$payment['booking_id']]);
            if ($booking) {
                Whatsapp::notify('payment_received', $booking['customer_mobile'], [
                    'amount' => money($payment['amount']), 'booking_code' => $booking['code'],
                ]);
            }
            Session::flash('success', 'Payment approved and booking confirmed.');
        } elseif ($action === 'reject') {
            PaymentProcessor::reject((int)$payment['id'], (int)Auth::id());
            ActivityLog::record('payment.reject', 'payment', $payment['id']);
            Session::flash('success', 'Payment rejected.');
        }
        return $this->redirect('/admin/payments');
    }
}
