<?php
namespace App\Core;

/**
 * Key/value settings loaded from the DB once per request and cached.
 * Encrypted settings (payment/whatsapp/github secrets) are transparently
 * decrypted on read via get()/getSecret().
 */
class Settings
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function boot(): void
    {
        if (self::$loaded) {
            return;
        }
        try {
            $rows = Database::fetchAll("SELECT `key`, `value`, `is_encrypted` FROM {p}settings");
            foreach ($rows as $r) {
                self::$cache[$r['key']] = [
                    'value'     => $r['value'],
                    'encrypted' => (int)$r['is_encrypted'] === 1,
                ];
            }
        } catch (\Throwable $e) {
            // Table may not exist yet during install — ignore.
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        if (!array_key_exists($key, self::$cache)) {
            return $default;
        }
        $entry = self::$cache[$key];
        if ($entry['encrypted'] && $entry['value'] !== null && $entry['value'] !== '') {
            try {
                return Crypto::decrypt($entry['value']);
            } catch (\Throwable $e) {
                return $default;
            }
        }
        return $entry['value'];
    }

    /** Alias to make intent explicit for sensitive values. */
    public static function getSecret(string $key, $default = null)
    {
        return self::get($key, $default);
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$cache);
    }

    public static function set(string $key, $value, bool $encrypted = false): void
    {
        $stored = $encrypted && $value !== null && $value !== '' ? Crypto::encrypt((string)$value) : $value;
        $exists = Database::scalar("SELECT COUNT(*) FROM {p}settings WHERE `key` = ?", [$key]);
        if ($exists) {
            Database::update('settings', ['value' => $stored, 'is_encrypted' => $encrypted ? 1 : 0], ['key' => $key]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => $stored, 'is_encrypted' => $encrypted ? 1 : 0]);
        }
        self::$cache[$key] = ['value' => $stored, 'encrypted' => $encrypted];
    }

    /** All non-encrypted settings as a plain map (for admin forms). */
    public static function all(): array
    {
        $out = [];
        foreach (self::$cache as $k => $entry) {
            $out[$k] = $entry['encrypted'] ? self::get($k) : $entry['value'];
        }
        return $out;
    }
}
