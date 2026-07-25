<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;
use App\Core\Request;

/**
 * Self-service registration for shop partners and vehicle agencies, plus the
 * shared partner-code generators used by the admin panel.
 *
 * New partners are created in a PENDING state (status=inactive, kyc=pending) so
 * their QR/vehicles do not go live until an admin approves them — unless
 * registration_auto_approve is enabled.
 */
class PartnerRegistration
{
    private const MAX_PER_IP_PER_HOUR = 5;

    // ---- code generators (shared with the admin controllers) --------------

    public static function nextShopCode(): string
    {
        $last = Database::scalar("SELECT code FROM {p}shops WHERE code LIKE 'SHOP-DWK-%' ORDER BY id DESC LIMIT 1");
        $n = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) { $n = (int)$m[1]; }
        return sprintf('SHOP-DWK-%03d', $n + 1);
    }

    public static function nextAgencyCode(): string
    {
        $last = Database::scalar("SELECT code FROM {p}agencies WHERE code LIKE 'AGN-DWK-%' ORDER BY id DESC LIMIT 1");
        $n = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) { $n = (int)$m[1]; }
        return sprintf('AGN-DWK-%03d', $n + 1);
    }

    // ---- registration -----------------------------------------------------

    /**
     * Validate + create a partner. $type is 'shop' or 'agency'.
     * @return array{ok:bool, error?:string, id?:int, user_id?:int, code?:string, approved?:bool}
     */
    public static function register(string $type, array $in): array
    {
        $type = $type === 'agency' ? 'agency' : 'shop';

        if (Settings::get($type . '_registration_enabled', '1') !== '1') {
            return ['ok' => false, 'error' => 'Registration is currently closed. Please contact us.'];
        }
        if (self::tooManyAttempts()) {
            return ['ok' => false, 'error' => 'Too many registration attempts. Please try again later.'];
        }

        $name     = trim((string)($in['name'] ?? ''));
        $owner    = trim((string)($in['owner_name'] ?? ''));
        $mobile   = preg_replace('/[^0-9]/', '', (string)($in['mobile'] ?? ''));
        $email    = trim((string)($in['email'] ?? ''));
        $password = (string)($in['password'] ?? '');

        if ($name === '')                                { return ['ok' => false, 'error' => 'Business name is required.']; }
        if ($owner === '')                               { return ['ok' => false, 'error' => 'Owner name is required.']; }
        if (!preg_match('/^[6-9]\d{9}$/', (string)$mobile)) { return ['ok' => false, 'error' => 'Enter a valid 10-digit mobile number.']; }
        if (strlen($password) < 8)                       { return ['ok' => false, 'error' => 'Password must be at least 8 characters.']; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid email address (or leave it blank).'];
        }
        if (empty($in['terms'])) {
            return ['ok' => false, 'error' => 'Please accept the Terms & Conditions.'];
        }
        if (Database::scalar("SELECT COUNT(*) FROM {p}users WHERE mobile=?", [$mobile])) {
            return ['ok' => false, 'error' => 'This mobile number is already registered. Please log in instead.'];
        }

        $autoApprove = Settings::get('registration_auto_approve', '0') === '1';
        $status = $autoApprove ? 'active' : 'inactive';
        $kyc    = $autoApprove ? 'approved' : 'pending';

        $table = $type === 'shop' ? 'shops' : 'agencies';
        $code  = $type === 'shop' ? self::nextShopCode() : self::nextAgencyCode();

        $fields = [
            'code'       => $code,
            'name'       => $name,
            'owner_name' => $owner,
            'mobile'     => $mobile,
            'whatsapp'   => $mobile,
            'email'      => $email ?: null,
            'address'    => trim((string)($in['address'] ?? '')) ?: null,
            'status'     => $status,
            'source'     => 'self',
        ];
        if ($type === 'shop') {
            $fields['area']            = trim((string)($in['area'] ?? '')) ?: null;
            $fields['city']            = trim((string)($in['city'] ?? '')) ?: 'Dwarka';
            $fields['kyc_status']      = $kyc;
            $fields['commission_type'] = 'inherit';
        } else {
            $fields['commission_type'] = 'inherit';
        }

        $result = Database::transaction(function () use ($table, $fields, $type, $name, $mobile, $email, $password) {
            $id = Database::insert($table, $fields);
            Database::insert('wallets', ['owner_type' => $type, 'owner_id' => $id, 'balance' => 0]);
            $userId = PanelUser::upsert($type, $id, $name, $mobile, $email ?: null, $password);
            return ['id' => $id, 'user_id' => $userId];
        });

        self::logAttempt();
        self::notifyAdmin($type, $name, $mobile, $code);

        return [
            'ok'       => true,
            'id'       => (int)$result['id'],
            'user_id'  => (int)$result['user_id'],
            'code'     => $code,
            'approved' => $autoApprove,
        ];
    }

    // ---- helpers ----------------------------------------------------------

    private static function tooManyAttempts(): bool
    {
        $since = date('Y-m-d H:i:s', time() - 3600);
        $count = (int)Database::scalar(
            "SELECT COUNT(*) FROM {p}login_attempts WHERE ip=? AND login='__register__' AND created_at > ?",
            [Request::ip(), $since]
        );
        return $count >= self::MAX_PER_IP_PER_HOUR;
    }

    private static function logAttempt(): void
    {
        // Reuse login_attempts as a lightweight per-IP rate-limit ledger.
        Database::insert('login_attempts', [
            'login'      => '__register__',
            'ip'         => Request::ip(),
            'success'    => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function notifyAdmin(string $type, string $name, string $mobile, string $code): void
    {
        $adminWa = (string)Settings::get('contact_whatsapp', '');
        if ($adminWa === '') { return; }
        $label = $type === 'shop' ? 'shop partner' : 'agency';
        Whatsapp::queue(
            $adminWa,
            "New {$label} registration: {$name} ({$code}), mobile {$mobile}. Approve it in your admin panel.",
            'partner_registration'
        );
    }
}
