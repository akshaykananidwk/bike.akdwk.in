<?php
/**
 * Global helper functions available everywhere after bootstrap.
 */

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Settings;
use App\Core\Auth;

if (!function_exists('e')) {
    /** HTML-escape output (XSS guard). */
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('__')) {
    /** Translate a key. */
    function __(string $key, array $params = []): string
    {
        return Lang::get($key, $params);
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $root = rtrim(Settings::get('site_url', '') ?: guess_base_url(), '/');
        return $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('guess_base_url')) {
    function guess_base_url(): string
    {
        $https = (($_SERVER['HTTPS'] ?? '') === 'on')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = rtrim($dir === '/' ? '' : $dir, '/');
        return "{$scheme}://{$host}{$dir}";
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $v = Settings::get('asset_version', '1');
        return base_url('assets/' . ltrim($path, '/')) . '?v=' . $v;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $path): string
    {
        return base_url('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        if (!preg_match('#^https?://#', $url)) {
            $url = base_url($url);
        }
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        $old = \App\Core\Session::flash('old') ?? [];
        // Re-flash so multiple fields on the same page can read it.
        if (!empty($old)) { \App\Core\Session::flash('old', $old); }
        return $old[$key] ?? $default;
    }
}

if (!function_exists('money')) {
    /** Format a rupee amount. */
    function money($amount): string
    {
        $symbol = Settings::get('currency_symbol', '₹');
        return $symbol . number_format((float)$amount, 2);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        return Settings::get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('mask_account')) {
    /** Mask a bank account number, showing only the last 4 digits. */
    function mask_account(?string $acc): string
    {
        if (!$acc) { return ''; }
        $len = strlen($acc);
        return $len <= 4 ? str_repeat('X', $len) : str_repeat('X', $len - 4) . substr($acc, -4);
    }
}

if (!function_exists('gen_code')) {
    /** Generate a booking/reference code like DWK-2026-00184. */
    function gen_code(string $prefix, int $seq, ?int $year = null): string
    {
        $year = $year ?? (int)date('Y');
        return sprintf('%s-%d-%05d', $prefix, $year, $seq);
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('otp_required')) {
    /**
     * Mobile OTP at checkout is required only when the admin has it enabled AND
     * WhatsApp is configured to actually deliver it. Otherwise the booking flow
     * proceeds with name + mobile so customers are never blocked.
     */
    function otp_required(): bool
    {
        return \App\Core\Settings::get('otp_enabled', '1') === '1'
            && \App\Services\Whatsapp::isConfigured();
    }
}

if (!function_exists('log_line')) {
    function log_line(string $file, string $message): void
    {
        @file_put_contents(
            BASE_PATH . '/logs/' . $file,
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND
        );
    }
}
