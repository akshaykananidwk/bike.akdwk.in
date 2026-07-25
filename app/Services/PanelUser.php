<?php
namespace App\Services;

use App\Core\Database;

/**
 * Creates/updates the login account for a shop partner or agency owner.
 * One user row (role=shop|agency) linked to the shop_id/agency_id.
 */
class PanelUser
{
    /** @return int user id (0 if nothing to do) */
    public static function upsert(string $role, int $ownerId, string $name, ?string $mobile, ?string $email, ?string $password): int
    {
        if (!$mobile) { return 0; }
        $col = $role === 'shop' ? 'shop_id' : 'agency_id';
        $existing = Database::fetch("SELECT * FROM {p}users WHERE role=? AND {$col}=? LIMIT 1", [$role, $ownerId]);

        $fields = [
            'role'   => $role,
            $col     => $ownerId,
            'name'   => $name,
            'mobile' => $mobile,
            'email'  => $email ?: null,
            'status' => 'active',
        ];
        if ($password && strlen($password) >= 6) {
            $fields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($existing) {
            unset($fields['role'], $fields[$col]);
            // Don't wipe the password if none supplied.
            if (!isset($fields['password'])) { /* keep */ }
            Database::update('users', $fields, ['id' => $existing['id']]);
            return (int)$existing['id'];
        }

        // New account requires a password.
        if (!isset($fields['password'])) {
            return 0;
        }
        // Avoid mobile collision with another account.
        if (Database::scalar("SELECT COUNT(*) FROM {p}users WHERE mobile=?", [$mobile])) {
            $fields['mobile'] = $mobile . '-' . $role . $ownerId;
        }
        return Database::insert('users', $fields);
    }
}
