<?php
namespace App\Core;

/**
 * Bilingual (English / Gujarati) translation layer.
 * Base strings live in lang/en.php & lang/gu.php; DB-managed overrides can be
 * layered on top via the `translations` table (admin-editable).
 */
class Lang
{
    private static string $locale = 'en';
    private static array $strings = [];

    public static function boot(): void
    {
        $default = Settings::get('default_language', 'en');
        $locale = Session::get('locale')
            ?? ($_COOKIE['locale'] ?? $default);
        self::setLocale(in_array($locale, ['en', 'gu'], true) ? $locale : 'en');
    }

    public static function setLocale(string $locale): void
    {
        $locale = in_array($locale, ['en', 'gu'], true) ? $locale : 'en';
        self::$locale = $locale;
        Session::set('locale', $locale);
        setcookie('locale', $locale, time() + 86400 * 365, '/');

        $file = BASE_PATH . "/lang/{$locale}.php";
        self::$strings = is_file($file) ? (require $file) : [];

        // DB overrides (optional; table may not exist during install).
        try {
            $rows = Database::fetchAll(
                "SELECT `key`, `value` FROM {p}translations WHERE locale = ?",
                [$locale]
            );
            foreach ($rows as $r) {
                self::$strings[$r['key']] = $r['value'];
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /** Translate a key with optional {var} replacements. */
    public static function get(string $key, array $params = []): string
    {
        $str = self::$strings[$key] ?? $key;
        foreach ($params as $k => $v) {
            $str = str_replace('{' . $k . '}', (string)$v, $str);
        }
        return $str;
    }

    public static function isGujarati(): bool
    {
        return self::$locale === 'gu';
    }
}
