<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Settings;
use App\Core\Request;
use App\Services\Whatsapp;

/**
 * Inbound WhatsApp webhook. Accepts the same JSON shape as the outbound API
 * (api_key, session_id, phone, message), verifies the key, logs the message,
 * and supports simple auto-replies (e.g. a booking code -> booking status).
 */
class WhatsappController extends Controller
{
    public function inbound(): string
    {
        $data = Request::json();
        if (!$data) { $data = $_POST; }

        // Verify the shared key (inbound secret, else the API key).
        $expected = (string)(Settings::get('wa_inbound_secret', '') ?: Settings::getSecret('wa_api_key', ''));
        $provided = (string)($data['api_key'] ?? '');
        if ($expected === '' || !hash_equals($expected, $provided)) {
            return $this->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $phone   = Whatsapp::normalize((string)($data['phone'] ?? ''));
        $message = trim((string)($data['message'] ?? ''));

        Whatsapp::log('in', $phone, $message, 'received', $data);

        // Auto-reply: a booking code returns its status.
        $reply = $this->autoReply($message, $phone);
        if ($reply !== null) {
            Whatsapp::queue($phone, $reply, 'auto_reply');
        }

        return $this->json(['ok' => true, 'auto_reply' => $reply !== null]);
    }

    private function autoReply(string $message, string $phone): ?string
    {
        // Look for a booking code like DWK-2026-00184.
        if (preg_match('/DWK-\d{4}-\d{5}/i', $message, $m)) {
            $b = Database::fetch("SELECT * FROM {p}bookings WHERE code=?", [strtoupper($m[0])]);
            if ($b) {
                return "Booking {$b['code']}: " . strtoupper(str_replace('_', ' ', $b['status']))
                    . ". Pickup " . date('d M h:iA', strtotime($b['pickup_at']))
                    . ". Balance " . money($b['balance_amount']) . ".";
            }
            return "Sorry, we couldn't find booking {$m[0]}.";
        }
        // Greeting / help
        if (preg_match('/\b(hi|hello|hey|menu|help|namaste)\b/i', $message)) {
            return "Namaste! Reply with your booking code (e.g. DWK-2026-00001) to check status, or visit "
                . base_url('/') . " to rent a vehicle.";
        }
        return null;
    }
}
