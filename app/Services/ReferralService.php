<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Customer referral + loyalty credit.
 *
 * Every customer gets a share code. When a friend books with that code, both
 * sides earn credit which is automatically applied to the next booking.
 * Loyalty points are awarded on completed rides and convert to credit.
 */
class ReferralService
{
    /** Get (or create) the share code for a customer mobile. */
    public static function codeFor(string $mobile): string
    {
        $c = Database::fetch("SELECT id, referral_code FROM {p}customers WHERE mobile=?", [$mobile]);
        if (!$c) { return ''; }
        if (!empty($c['referral_code'])) { return (string)$c['referral_code']; }

        // Short, readable, unique: DWK + 5 chars from the mobile + random.
        do {
            $code = 'DWK' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $taken = Database::scalar("SELECT COUNT(*) FROM {p}customers WHERE referral_code=?", [$code]);
        } while ($taken);

        Database::update('customers', ['referral_code' => $code], ['id' => $c['id']]);
        return $code;
    }

    public static function creditBalance(string $mobile): float
    {
        if (Settings::get('referral_enabled', '1') !== '1' && Settings::get('loyalty_enabled', '1') !== '1') {
            return 0.0;
        }
        return (float)Database::scalar("SELECT credit_balance FROM {p}customers WHERE mobile=?", [$mobile]);
    }

    /** Deduct credit used on a booking. Never goes below zero. */
    public static function consumeCredit(string $mobile, float $amount, int $bookingId): void
    {
        if ($amount <= 0) { return; }
        Database::run(
            "UPDATE {p}customers SET credit_balance = GREATEST(0, credit_balance - ?) WHERE mobile=?",
            [round($amount, 2), $mobile]
        );
    }

    public static function addCredit(string $mobile, float $amount): void
    {
        if ($amount <= 0) { return; }
        Database::run("UPDATE {p}customers SET credit_balance = credit_balance + ? WHERE mobile=?", [round($amount, 2), $mobile]);
    }

    /**
     * Record that this booking came from a friend's referral code.
     * The reward is only credited once the booking is confirmed (see reward()).
     */
    public static function attachReferral(string $mobile, string $code, int $bookingId): void
    {
        if (Settings::get('referral_enabled', '1') !== '1') { return; }
        $code = strtoupper(trim($code));
        if ($code === '') { return; }

        $referrer = Database::fetch("SELECT mobile FROM {p}customers WHERE referral_code=?", [$code]);
        if (!$referrer || $referrer['mobile'] === $mobile) { return; }   // no self-referral

        // A customer can only be referred once, on their first booking.
        $already = Database::scalar("SELECT COUNT(*) FROM {p}customer_referrals WHERE referred_mobile=?", [$mobile]);
        if ($already) { return; }
        $priorBookings = (int)Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE customer_mobile=? AND id<>?", [$mobile, $bookingId]);
        if ($priorBookings > 0) { return; }

        Database::insert('customer_referrals', [
            'referrer_mobile' => $referrer['mobile'],
            'referred_mobile' => $mobile,
            'booking_id'      => $bookingId,
            'reward_referrer' => (float)Settings::get('referral_reward_referrer', 100),
            'reward_referred' => (float)Settings::get('referral_reward_referred', 100),
            'status'          => 'pending',
        ]);
        Database::update('customers', ['referred_by' => $code], ['mobile' => $mobile]);
    }

    /**
     * Credit both sides once a referred booking is confirmed, and award loyalty
     * points for the paid amount. Called from BookingService::confirm().
     */
    public static function rewardForBooking(int $bookingId): void
    {
        $b = Database::fetch("SELECT customer_mobile, paid_amount FROM {p}bookings WHERE id=?", [$bookingId]);
        if (!$b || !$b['customer_mobile']) { return; }
        $mobile = (string)$b['customer_mobile'];

        // --- Referral payout ---------------------------------------------
        if (Settings::get('referral_enabled', '1') === '1') {
            $ref = Database::fetch(
                "SELECT * FROM {p}customer_referrals WHERE booking_id=? AND status='pending'",
                [$bookingId]
            );
            if ($ref) {
                Database::transaction(function () use ($ref) {
                    self::addCredit((string)$ref['referrer_mobile'], (float)$ref['reward_referrer']);
                    self::addCredit((string)$ref['referred_mobile'], (float)$ref['reward_referred']);
                    Database::update('customer_referrals', ['status' => 'credited'], ['id' => $ref['id']]);
                });
                Whatsapp::notify('referral_reward', (string)$ref['referrer_mobile'], [
                    'customer_name' => '',
                    'amount'        => money($ref['reward_referrer']),
                ]);
            }
        }

        // --- Loyalty points ----------------------------------------------
        if (Settings::get('loyalty_enabled', '1') === '1') {
            $per100 = (float)Settings::get('loyalty_points_per_100', 5);
            $points = (int)floor(((float)$b['paid_amount'] / 100) * $per100);
            if ($points > 0) {
                Database::run(
                    "UPDATE {p}customers SET loyalty_points = loyalty_points + ?, total_bookings = total_bookings + 1 WHERE mobile=?",
                    [$points, $mobile]
                );
            } else {
                Database::run("UPDATE {p}customers SET total_bookings = total_bookings + 1 WHERE mobile=?", [$mobile]);
            }
        }
    }

    /** Convert loyalty points into spendable credit. */
    public static function redeemPoints(string $mobile, int $points): array
    {
        $c = Database::fetch("SELECT * FROM {p}customers WHERE mobile=?", [$mobile]);
        if (!$c) { return ['ok' => false, 'error' => 'Customer not found.']; }
        $points = max(0, min($points, (int)$c['loyalty_points']));
        if ($points <= 0) { return ['ok' => false, 'error' => 'Not enough points.']; }

        $value = round($points * (float)Settings::get('loyalty_point_value', 1), 2);
        Database::transaction(function () use ($c, $points, $value) {
            Database::run("UPDATE {p}customers SET loyalty_points = loyalty_points - ?, credit_balance = credit_balance + ? WHERE id=?",
                [$points, $value, $c['id']]);
        });
        return ['ok' => true, 'points' => $points, 'credit' => $value];
    }
}
