<?php
namespace App\Core;

/**
 * AES-256-GCM encryption for secrets at rest (GitHub token, WhatsApp key,
 * gateway secrets, bank details). Key derives from APP_KEY in config.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private static function key(): string
    {
        $appKey = $GLOBALS['app_config']['app']['key'] ?? '';
        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY is not set.');
        }
        // Normalise to 32 raw bytes.
        if (str_starts_with($appKey, 'base64:')) {
            return base64_decode(substr($appKey, 7), true) ?: hash('sha256', $appKey, true);
        }
        return hash('sha256', $appKey, true);
    }

    public static function encrypt(string $plaintext): string
    {
        $iv  = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 28) {
            throw new \RuntimeException('Invalid ciphertext.');
        }
        $iv     = substr($raw, 0, 12);
        $tag    = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain  = openssl_decrypt($cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new \RuntimeException('Decryption failed.');
        }
        return $plain;
    }

    /** Generate a fresh random APP_KEY (base64: prefixed, 32 bytes). */
    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /** Mask a secret for display, e.g. "ghp_1a...ef42" -> "ghp_1a••••••ef42". */
    public static function mask(?string $secret): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }
        $len = strlen($secret);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }
        return substr($secret, 0, 4) . str_repeat('•', 6) . substr($secret, -4);
    }
}
