<?php
namespace App\Core;

/**
 * Read-only accessor for the current HTTP request.
 */
class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    /** GET/POST value, trimmed if string. */
    public static function input(string $key, $default = null)
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function post(string $key, $default = null)
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function query(string $key, $default = null)
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = explode(',', $_SERVER[$h])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /** JSON request body decoded as array. */
    public static function json(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xrw    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strpos($accept, 'application/json') !== false
            || strtolower($xrw) === 'xmlhttprequest';
    }

    public static function bearerToken(): ?string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (stripos($h, 'Bearer ') === 0) {
            return substr($h, 7);
        }
        return null;
    }
}
