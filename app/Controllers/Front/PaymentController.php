<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Uploader;
use App\Services\BookingService;
use App\Services\Whatsapp;
use App\Services\Payments\RazorpayGateway;
use App\Services\Payments\PaymentProcessor;

class PaymentController extends Controller
{
    private function booking(string $code): ?array
    {
        return Database::fetch("SELECT * FROM {p}bookings WHERE code=?", [$code]);
    }

    // -- Razorpay -----------------------------------------------------------

    /** Create an order and render the checkout launcher. */
    public function razorpayCreate(array $p): string
    {
        $booking = $this->booking($p['code'] ?? '');
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }
        if ($booking['status'] !== 'pending_payment') { return $this->redirect('/booking/' . $booking['code'] . '/success'); }

        $gw = new RazorpayGateway();
        if (!$gw->isEnabled()) { Session::flash('error', 'Online payment is not available right now.'); return $this->redirect('/checkout/' . $booking['code']); }

        try {
            $order = $gw->createOrder($booking, (float)$booking['advance_amount']);
        } catch (\Throwable $e) {
            Session::flash('error', 'Could not start payment. Please try another method.');
            log_line('app.log', 'razorpay create: ' . $e->getMessage());
            return $this->redirect('/checkout/' . $booking['code']);
        }
        // Persist the created order for reconciliation.
        Database::insert('payments', [
            'booking_id' => $booking['id'], 'gateway' => 'razorpay', 'amount' => $booking['advance_amount'],
            'status' => 'created', 'gateway_order_id' => $order['order_id'],
        ]);

        return $this->view('front/razorpay', [
            'title' => 'Pay — ' . $booking['code'], 'booking' => $booking, 'order' => $order,
        ], 'front');
    }

    /** Client success callback — verify signature server-side. */
    public function razorpayCallback(array $p): string
    {
        $this->verifyCsrf();
        $booking = $this->booking($p['code'] ?? '');
        if (!$booking) { return $this->json(['ok' => false], 404); }

        $gw = new RazorpayGateway();
        $result = $gw->verifyCallback(Request::all());
        if (!$result['ok']) {
            return $this->json(['ok' => false, 'error' => 'Payment verification failed.'], 422);
        }
        PaymentProcessor::record((int)$booking['id'], 'razorpay', (float)$booking['advance_amount'], 'paid', [
            'order_id'   => $result['order_id'],
            'payment_id' => $result['payment_id'],
            'signature'  => $result['raw']['razorpay_signature'] ?? null,
            'response'   => $result['raw'],
        ]);
        return $this->json(['ok' => true, 'redirect' => base_url('/booking/' . $booking['code'] . '/success')]);
    }

    /** Server-to-server webhook — idempotent. */
    public function razorpayWebhook(): string
    {
        $raw = file_get_contents('php://input') ?: '';
        $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
        $gw = new RazorpayGateway();
        $result = $gw->verifyWebhook($raw, $headers);

        Whatsapp::log('in', null, 'razorpay-webhook', $result['ok'] ? 'verified' : 'invalid', $raw);

        if (!$result['ok']) { http_response_code(400); return 'invalid signature'; }

        if (in_array($result['event'], ['payment.captured', 'order.paid'], true) && $result['order_id']) {
            $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=(SELECT booking_id FROM {p}payments WHERE gateway_order_id=? LIMIT 1)", [$result['order_id']]);
            if ($booking) {
                PaymentProcessor::record((int)$booking['id'], 'razorpay', (float)$result['amount'] ?: (float)$booking['advance_amount'], 'paid', [
                    'order_id' => $result['order_id'], 'payment_id' => $result['payment_id'], 'response' => $result['raw'],
                ]);
            }
        }
        http_response_code(200);
        return 'ok';
    }

    // -- UPI QR -------------------------------------------------------------

    public function upi(array $p): string
    {
        $booking = $this->booking($p['code'] ?? '');
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }
        if ($booking['status'] !== 'pending_payment') { return $this->redirect('/booking/' . $booking['code'] . '/success'); }

        $agency = $booking['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$booking['agency_id']]) : null;
        // All payments route to the platform (admin) account; the agency is settled
        // from the wallet after the hold period. Fall back to agency UPI only if
        // the platform hasn't set a UPI and payments_to_platform is off.
        $toPlatform = setting('payments_to_platform', '1') === '1';
        if ($toPlatform) {
            $upiId   = (string)setting('platform_upi_id', '');
            $qrImage = setting('platform_upi_qr') ?: null;
            $payee   = setting('platform_payee_name') ?: setting('site_name', 'Dwarka Rental');
        } else {
            $upiId   = $agency['upi_id'] ?? setting('platform_upi_id', '');
            $qrImage = $agency['upi_qr_image'] ?? null;
            $payee   = $agency['name'] ?? setting('site_name', 'Dwarka Rental');
        }
        $amount = (float)$booking['advance_amount'];
        $deepLink = $upiId ? 'upi://pay?pa=' . rawurlencode($upiId) . '&pn=' . rawurlencode($payee) . '&am=' . $amount . '&cu=INR&tn=' . rawurlencode($booking['code']) : '';

        if (Request::isPost()) {
            $this->verifyCsrf();
            $utr = trim((string)Request::post('utr'));
            $screenshot = null;
            $file = Request::file('screenshot');
            if ($file && ($file['error'] ?? 1) === UPLOAD_ERR_OK) {
                $r = Uploader::handle($file, 'payments', ['jpg','jpeg','png','webp','pdf']);
                if ($r['ok']) { $screenshot = $r['path']; }
            }
            if ($utr === '' && $screenshot === null) {
                Session::flash('error', 'Please enter the UTR/reference number or upload a payment screenshot.');
                return $this->redirect('/pay/' . $booking['code'] . '/upi');
            }
            PaymentProcessor::record((int)$booking['id'], 'upi', $amount, 'pending_verification', [
                'utr' => $utr ?: null, 'screenshot' => $screenshot,
            ]);
            Session::flash('success', 'Payment submitted. We will confirm shortly.');
            return $this->redirect('/booking/' . $booking['code'] . '/success');
        }

        return $this->view('front/upi', [
            'title' => 'UPI Payment — ' . $booking['code'],
            'booking' => $booking, 'agency' => $agency, 'upiId' => $upiId,
            'qrImage' => $qrImage, 'deepLink' => $deepLink, 'amount' => $amount,
        ], 'front');
    }

    // -- Cash / pay at pickup ----------------------------------------------

    public function cash(array $p): string
    {
        $booking = $this->booking($p['code'] ?? '');
        if (!$booking) { http_response_code(404); return $this->view('errors/404', []); }
        if (Request::isPost()) { $this->verifyCsrf(); }
        if ($booking['status'] !== 'pending_payment') { return $this->redirect('/booking/' . $booking['code'] . '/success'); }
        if (setting('cash_enabled') !== '1') { Session::flash('error', 'Cash payment is not available.'); return $this->redirect('/checkout/' . $booking['code']); }

        $bookingFee = (float)setting('booking_fee', 0);
        // Record the online booking fee (may be 0) and reserve the booking.
        PaymentProcessor::record((int)$booking['id'], 'cash', $bookingFee, 'paid', ['response' => ['method' => 'cash_at_pickup']]);
        // Cash reservation confirms the booking even if the advance isn't fully paid online.
        BookingService::confirm((int)$booking['id']);

        Session::flash('success', 'Booking reserved! Please pay the balance at pickup.');
        return $this->redirect('/booking/' . $booking['code'] . '/success');
    }
}
