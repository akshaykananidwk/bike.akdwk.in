<?php
namespace App\Core;

/**
 * Authentication + authorization for staff/admin/shop/agency users.
 * Passwords use password_hash()/password_verify(). Login is rate-limited
 * and locks the account after repeated failures.
 */
class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES  = 15;

    /** Currently authenticated user row, or null. */
    public static function user(): ?array
    {
        $id = Session::get('auth_user_id');
        if (!$id) {
            return null;
        }
        static $cache = null;
        if ($cache === null || (int)$cache['id'] !== (int)$id) {
            $cache = Database::fetch("SELECT * FROM {p}users WHERE id = ? AND status = 'active'", [$id]);
        }
        return $cache ?: null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        $r = self::role();
        return $r !== null && in_array($r, $roles, true);
    }

    /**
     * Attempt login by mobile or email + password.
     * Returns [ok=>bool, error=>string|null, user=>array|null].
     */
    public static function attempt(string $login, string $password): array
    {
        $ip = Request::ip();
        if (self::isLockedOut($login, $ip)) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Try again later.', 'user' => null];
        }

        $user = Database::fetch(
            "SELECT * FROM {p}users WHERE (mobile = ? OR email = ?) LIMIT 1",
            [$login, $login]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            self::recordAttempt($login, $ip, false);
            return ['ok' => false, 'error' => 'Invalid credentials.', 'user' => null];
        }
        if (($user['status'] ?? '') !== 'active') {
            return ['ok' => false, 'error' => 'Account is not active.', 'user' => null];
        }

        // Rehash if algorithm parameters changed.
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $user['id']]);
        }

        self::recordAttempt($login, $ip, true);
        self::login($user);
        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('auth_user_id', (int)$user['id']);
        Session::set('auth_role', $user['role']);
        Database::update('users', ['last_login_at' => gmdate('Y-m-d H:i:s')], ['id' => $user['id']]);
    }

    public static function logout(): void
    {
        Session::forget('auth_user_id');
        Session::forget('auth_role');
        Session::regenerate();
    }

    // ---- impersonation (admin "login as") ---------------------------------

    /** Super admin logs in as another user, remembering who to return to. */
    public static function impersonate(int $userId): bool
    {
        if (!self::is('super_admin')) {
            return false;
        }
        $target = Database::fetch("SELECT * FROM {p}users WHERE id=? AND status='active'", [$userId]);
        if (!$target) {
            return false;
        }
        $adminId = self::id();
        self::login($target);              // regenerates id but keeps session data
        $_SESSION['_impersonator'] = $adminId;
        return true;
    }

    public static function isImpersonating(): bool
    {
        return !empty($_SESSION['_impersonator']);
    }

    /** Return to the original admin account. */
    public static function stopImpersonating(): void
    {
        if (empty($_SESSION['_impersonator'])) {
            return;
        }
        $adminId = (int)$_SESSION['_impersonator'];
        unset($_SESSION['_impersonator']);
        $admin = Database::fetch("SELECT * FROM {p}users WHERE id=? AND status='active'", [$adminId]);
        if ($admin) {
            self::login($admin);
        }
    }

    /** Require authentication + optional role list; redirect if unmet. */
    public static function require(string $loginUrl, string ...$roles): void
    {
        if (!self::check()) {
            Session::flash('error', 'Please log in to continue.');
            redirect($loginUrl);
        }
        if ($roles && !self::is(...$roles)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    // ---- rate limiting ----------------------------------------------------

    private static function recordAttempt(string $login, string $ip, bool $success): void
    {
        Database::insert('login_attempts', [
            'login'      => substr($login, 0, 190),
            'ip'         => $ip,
            'success'    => $success ? 1 : 0,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        if ($success) {
            // Clear the failure streak.
            Database::run(
                "DELETE FROM {p}login_attempts WHERE login = ? AND success = 0",
                [$login]
            );
        }
    }

    private static function isLockedOut(string $login, string $ip): bool
    {
        $since = gmdate('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);
        $count = (int)Database::scalar(
            "SELECT COUNT(*) FROM {p}login_attempts
             WHERE (login = ? OR ip = ?) AND success = 0 AND created_at > ?",
            [$login, $ip, $since]
        );
        return $count >= self::MAX_ATTEMPTS;
    }
}
