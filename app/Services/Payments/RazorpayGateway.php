<?php
namespace App\Services\Payments;

use App\Core\Settings;

/**
 * Razorpay gateway. Uses the Orders API + client checkout, with server-side
 * HMAC-SHA256 signature verification on both the callback and the webhook.
 * Keys are read from encrypted settings; nothing is trusted from the client.
 */
class RazorpayGateway implements GatewayInterface
{
    public function key(): string { return 'razorpay'; }

    public function isEnabled(): bool
    {
        return Settings::get('razorpay_enabled') === '1'
            && (string)Settings::getSecret('razorpay_key_id', '') !== ''
            && (string)Settings::getSecret('razorpay_key_secret', '') !== '';
    }

    public function publicKey(): string
    {
        return (string)Settings::getSecret('razorpay_key_id', '');
    }

    private function secret(): string
    {
        return (string)Settings::getSecret('razorpay_key_secret', '');
    }

    public function createOrder(array $booking, float $amount): array
    {
        $paise = (int)round($amount * 100);
        $payload = json_encode([
            'amount'          => $paise,
            'currency'        => Settings::get('currency_code', 'INR'),
            'receipt'         => $booking['code'],
            'payment_capture' => 1,
            'notes'           => ['booking_code' => $booking['code']],
        ]);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_USERPWD        => $this->publicKey() . ':' . $this->secret(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string)$resp, true) ?: [];
        if ($http < 200 || $http >= 300 || empty($data['id'])) {
            throw new \RuntimeException('Razorpay order creation failed: ' . ($data['error']['description'] ?? 'HTTP ' . $http));
        }
        return [
            'order_id' => $data['id'],
            'amount'   => $paise,
            'currency' => $data['currency'] ?? 'INR',
            'key_id'   => $this->publicKey(),
            'raw'      => $data,
        ];
    }

    public function verifyCallback(array $payload): array
    {
        $orderId   = (string)($payload['razorpay_order_id'] ?? '');
        $paymentId = (string)($payload['razorpay_payment_id'] ?? '');
        $signature = (string)($payload['razorpay_signature'] ?? '');
        $expected  = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->secret());
        $ok = $orderId !== '' && $paymentId !== '' && hash_equals($expected, $signature);
        return ['ok' => $ok, 'payment_id' => $paymentId, 'order_id' => $orderId, 'raw' => $payload];
    }

    public function verifyWebhook(string $rawBody, array $headers): array
    {
        $secret = (string)Settings::getSecret('razorpay_webhook_secret', '');
        $sig = $headers['x-razorpay-signature'] ?? $headers['X-Razorpay-Signature'] ?? '';
        $ok = false;
        if ($secret !== '' && $sig !== '') {
            $expected = hash_hmac('sha256', $rawBody, $secret);
            $ok = hash_equals($expected, $sig);
        }
        $data = json_decode($rawBody, true) ?: [];
        $entity = $data['payload']['payment']['entity'] ?? [];
        return [
            'ok'         => $ok,
            'event'      => $data['event'] ?? '',
            'payment_id' => $entity['id'] ?? '',
            'order_id'   => $entity['order_id'] ?? '',
            'amount'     => isset($entity['amount']) ? $entity['amount'] / 100 : 0.0,
            'raw'        => $data,
        ];
    }
}
