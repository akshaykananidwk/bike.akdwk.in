<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * WhatsApp integration. Outgoing messages are queued (whatsapp_queue) and
 * dispatched by cron with throttling/retries (Phase 6). Templates are stored in
 * whatsapp_templates (EN + GU). Credentials come from encrypted settings.
 */
class Whatsapp
{
    /** True when the WhatsApp API is enabled and its URL + key are configured. */
    public static function isConfigured(): bool
    {
        return Settings::get('wa_enabled') === '1'
            && (string)Settings::getSecret('wa_api_url', '') !== ''
            && (string)Settings::getSecret('wa_api_key', '') !== '';
    }

    /** Render a template body with {var} substitution for the given locale. */
    public static function render(string $key, array $vars = [], ?string $locale = null): string
    {
        $locale = $locale ?? Settings::get('default_language', 'en');
        $tpl = Database::fetch(
            "SELECT body FROM {p}whatsapp_templates WHERE `key`=? AND locale=? AND is_active=1",
            [$key, $locale]
        );
        if (!$tpl) {
            // Fall back to English, then to any locale.
            $tpl = Database::fetch("SELECT body FROM {p}whatsapp_templates WHERE `key`=? AND is_active=1 ORDER BY locale='en' DESC LIMIT 1", [$key]);
        }
        $body = $tpl['body'] ?? '';
        foreach ($vars as $k => $v) {
            $body = str_replace('{' . $k . '}', (string)$v, $body);
        }
        return $body;
    }

    /** Queue a raw message for later dispatch. */
    public static function queue(string $toNumber, string $message, ?string $templateKey = null, array $payload = []): int
    {
        $toNumber = self::normalize($toNumber);
        if ($toNumber === '') {
            return 0;
        }
        // Random small delay window to protect the sender number.
        $min = (int)Settings::get('wa_min_delay', 3);
        $max = max($min, (int)Settings::get('wa_max_delay', 8));
        $delay = random_int($min, $max);
        return Database::insert('whatsapp_queue', [
            'to_number'    => $toNumber,
            'template_key' => $templateKey,
            'message'      => $message,
            'payload'      => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'status'       => 'pending',
            'scheduled_at' => date('Y-m-d H:i:s', time() + $delay),
        ]);
    }

    /** Render a template and queue it in one step. */
    public static function notify(string $key, string $toNumber, array $vars = [], ?string $locale = null): int
    {
        $msg = self::render($key, $vars, $locale);
        if (trim($msg) === '') {
            return 0;
        }
        return self::queue($toNumber, $msg, $key, $vars);
    }

    /**
     * Actually send a message via the self-hosted API (POST JSON).
     * Returns [ok, http, response]. Never logs the API key.
     */
    public static function sendNow(string $toNumber, string $message): array
    {
        $url     = (string)Settings::getSecret('wa_api_url', '');
        $key     = (string)Settings::getSecret('wa_api_key', '');
        $session = (string)Settings::getSecret('wa_session_id', '');
        if ($url === '' || $key === '') {
            return ['ok' => false, 'http' => 0, 'response' => 'WhatsApp API not configured'];
        }
        $payload = json_encode([
            'api_key'    => $key,
            'session_id' => $session,
            'phone'      => self::normalize($toNumber),
            'message'    => $message,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $resp = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $ok = $resp !== false && $http >= 200 && $http < 300;
        return ['ok' => $ok, 'http' => $http, 'response' => $resp === false ? $err : (string)$resp];
    }

    /** Log an outgoing/incoming message (redacting nothing sensitive here). */
    public static function log(string $direction, ?string $number, ?string $message, string $status, $response = null, ?string $templateKey = null): void
    {
        Database::insert('whatsapp_logs', [
            'direction'     => $direction === 'in' ? 'in' : 'out',
            'to_number'     => $direction === 'out' ? self::normalize((string)$number) : null,
            'from_number'   => $direction === 'in' ? self::normalize((string)$number) : null,
            'template_key'  => $templateKey,
            'message'       => $message,
            'status'        => substr($status, 0, 40),
            'response_json' => is_string($response) ? $response : ($response ? json_encode($response) : null),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /** Normalise to a bare number (strip +, spaces); default India country code kept as-is. */
    public static function normalize(string $number): string
    {
        $n = preg_replace('/[^0-9]/', '', $number);
        return (string)$n;
    }
}
