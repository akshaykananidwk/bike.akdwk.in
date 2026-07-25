<?php
namespace App\Core;

/**
 * CSRF protection. A per-session token is embedded in every POST form and
 * AJAX header (X-CSRF-Token). verify() must be called on every state change.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function check(?string $token): bool
    {
        $expected = $_SESSION['_csrf'] ?? '';
        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /** Reads token from POST body or X-CSRF-Token header; aborts on failure. */
    public static function verify(): void
    {
        $token = $_POST['_csrf']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::check($token)) {
            http_response_code(419);
            if (Request::wantsJson()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'CSRF token mismatch']);
            } else {
                echo 'Invalid or expired security token. Please refresh and try again.';
            }
            exit;
        }
    }
}
