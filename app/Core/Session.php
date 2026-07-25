<?php
namespace App\Core;

/**
 * Secure session wrapper: HttpOnly, Secure, SameSite=Strict, idle timeout,
 * id regeneration on privilege change.
 */
class Session
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (($_SERVER['HTTPS'] ?? '') === 'on')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name($config['session']['name'] ?? 'DWKSESS');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        // Idle timeout
        $idle = (int)($config['session']['idle_timeout'] ?? 3600);
        $now  = time();
        if (isset($_SESSION['_last_activity']) && ($now - (int)$_SESSION['_last_activity']) > $idle) {
            self::destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = $now;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** One-time flash messages. */
    public static function flash(string $key, $value = null)
    {
        if ($value === null) {
            $v = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $v;
        }
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
}
