<?php
namespace App\Services\Payments;

use App\Core\Database;
use App\Services\BookingService;

/**
 * Records payments and applies them to bookings. Idempotent by
 * gateway_payment_id so duplicate webhooks/callbacks never double-credit.
 */
class PaymentProcessor
{
    /**
     * Record a successful/pending payment and (if paid) confirm the booking.
     *
     * @param array  $meta  order_id, payment_id, signature, utr, screenshot, response
     * @return array{payment_id:int, duplicate:bool}
     */
    public static function record(int $bookingId, string $gateway, float $amount, string $status, array $meta = []): array
    {
        return Database::transaction(function () use ($bookingId, $gateway, $amount, $status, $meta) {
            // Idempotency guard on the gateway payment id.
            if (!empty($meta['payment_id'])) {
                $existing = Database::fetch(
                    "SELECT id, status FROM {p}payments WHERE gateway_payment_id=? LIMIT 1",
                    [$meta['payment_id']]
                );
                if ($existing) {
                    return ['payment_id' => (int)$existing['id'], 'duplicate' => true];
                }
            }

            $paymentId = Database::insert('payments', [
                'booking_id'         => $bookingId,
                'gateway'            => $gateway,
                'amount'             => $amount,
                'currency'           => 'INR',
                'status'             => $status,
                'gateway_order_id'   => $meta['order_id'] ?? null,
                'gateway_payment_id' => $meta['payment_id'] ?? null,
                'gateway_signature'  => $meta['signature'] ?? null,
                'utr'                => $meta['utr'] ?? null,
                'screenshot'         => $meta['screenshot'] ?? null,
                'response_json'      => isset($meta['response']) ? (is_string($meta['response']) ? $meta['response'] : json_encode($meta['response'], JSON_UNESCAPED_UNICODE)) : null,
            ]);

            if ($status === 'paid') {
                self::applyPaid($bookingId, $amount);
            } elseif ($status === 'pending_verification') {
                Database::update('bookings', ['payment_status' => 'pending_verification'], ['id' => $bookingId]);
            }

            return ['payment_id' => $paymentId, 'duplicate' => false];
        });
    }

    /** Add a paid amount to a booking and confirm it if the advance is met. */
    public static function applyPaid(int $bookingId, float $amount): void
    {
        $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$bookingId]);
        if (!$booking) { return; }

        $paid = (float)$booking['paid_amount'] + $amount;
        $advance = (float)$booking['advance_amount'];
        $total = (float)$booking['total_amount'];

        $payStatus = $paid >= $total - 0.01 ? 'paid' : ($paid >= $advance - 0.01 ? 'advance_paid' : 'advance_paid');
        $balance = max(0, round($total - $paid, 2));

        Database::update('bookings', [
            'paid_amount'    => round($paid, 2),
            'balance_amount' => $balance,
            'payment_status' => $payStatus,
        ], ['id' => $bookingId]);

        // Confirm once the advance is covered.
        if ($paid >= $advance - 0.01 && $booking['status'] === 'pending_payment') {
            BookingService::confirm($bookingId);
        }
    }

    /** Mark a pending-verification payment as approved (admin/agency). */
    public static function approve(int $paymentId, int $verifiedBy): void
    {
        $payment = Database::fetch("SELECT * FROM {p}payments WHERE id=?", [$paymentId]);
        if (!$payment || $payment['status'] === 'paid') { return; }
        Database::transaction(function () use ($payment, $verifiedBy) {
            Database::update('payments', [
                'status'      => 'paid',
                'verified_by' => $verifiedBy,
                'verified_at' => now(),
            ], ['id' => $payment['id']]);
            self::applyPaid((int)$payment['booking_id'], (float)$payment['amount']);
        });
    }

    /** Reject a pending payment. */
    public static function reject(int $paymentId, int $verifiedBy): void
    {
        $payment = Database::fetch("SELECT * FROM {p}payments WHERE id=?", [$paymentId]);
        if (!$payment) { return; }
        Database::update('payments', ['status' => 'failed', 'verified_by' => $verifiedBy, 'verified_at' => now()], ['id' => $payment['id']]);
        // Leave the booking pending so the customer can retry/cancel.
        Database::update('bookings', ['payment_status' => 'unpaid'], ['id' => $payment['booking_id']]);
    }
}
