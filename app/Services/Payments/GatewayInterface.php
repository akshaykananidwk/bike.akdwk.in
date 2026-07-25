<?php
namespace App\Services\Payments;

/**
 * Contract for a payment gateway. New gateways (PhonePe, Cashfree, …) implement
 * this so they can be plugged in without touching the controller.
 */
interface GatewayInterface
{
    public function key(): string;                 // 'razorpay', 'phonepe', …
    public function isEnabled(): bool;

    /**
     * Create a payment order/session. Returns data the front-end needs to
     * launch checkout (e.g. order id, public key, amount in paise).
     */
    public function createOrder(array $booking, float $amount): array;

    /**
     * Verify a client-side success callback (signature check). Returns
     * ['ok'=>bool, 'payment_id'=>string, 'order_id'=>string, 'raw'=>array].
     */
    public function verifyCallback(array $payload): array;

    /**
     * Verify a server-to-server webhook. Returns
     * ['ok'=>bool, 'event'=>string, 'payment_id'=>string, 'order_id'=>string,
     *  'amount'=>float, 'raw'=>array].
     */
    public function verifyWebhook(string $rawBody, array $headers): array;
}
