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

    /** Payment gateway credentials (encrypted at rest, masked in the UI). */
    public function gateways(): string
    {
        if (!Auth::is('super_admin')) { http_response_code(403); return 'Forbidden'; }

        $secretKeys = [
            'razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret',
            'phonepe_merchant_id', 'phonepe_salt_key', 'phonepe_salt_index',
            'cashfree_app_id', 'cashfree_secret_key',
        ];

        if (Request::isPost()) {
            $this->verifyCsrf();
            foreach ($secretKeys as $k) {
                $v = Request::post($k);
                // Blank or still-masked field = leave the stored value untouched.
                if ($v === '' || $v === null || strpos((string)$v, '•') !== false) { continue; }
                \App\Core\Settings::set($k, $v, true);
            }
            foreach (['razorpay_enabled', 'upi_enabled', 'cash_enabled', 'phonepe_enabled', 'cashfree_enabled'] as $k) {
                \App\Core\Settings::set($k, Request::post($k) ? '1' : '0');
            }
            ActivityLog::record('payments.gateways');
            Session::flash('success', 'Payment gateway settings saved.');
            return $this->redirect('/admin/payments/gateways');
        }

        $masked = [];
        foreach ($secretKeys as $k) {
            $masked[$k] = \App\Core\Crypto::mask((string)\App\Core\Settings::getSecret($k, ''));
        }

        return $this->view('admin/payments/gateways', [
            'title'  => 'Payment Gateways', 'active' => 'payments',
            'masked' => $masked,
            's'      => \App\Core\Settings::all(),
        ], 'admin');
    }

    /** Validate the saved Razorpay keys by creating a ₹1 test order. */
    public function testGateway(): string
    {
        if (!Auth::is('super_admin')) { return $this->json(['ok' => false, 'error' => 'Forbidden'], 403); }
        $this->verifyCsrf();

        $gw = new \App\Services\Payments\RazorpayGateway();
        if (!$gw->isEnabled()) {
            return $this->json(['ok' => false, 'error' => 'Enable Razorpay and save your Key ID + Key Secret first.']);
        }
        try {
            $order = $gw->createOrder(['code' => 'TEST-' . date('His')], 1.00);
            return $this->json([
                'ok'      => true,
                'message' => 'Keys are valid. Test order created: ' . ($order['order_id'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
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
