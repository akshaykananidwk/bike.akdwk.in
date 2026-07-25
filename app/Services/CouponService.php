<?php
namespace App\Services;

use App\Core\Database;

/**
 * Coupon validation + discount calculation.
 */
class CouponService
{
    /**
     * Validate a coupon for a base amount.
     * @return array{ok:bool, error?:string, coupon?:array, discount?:float}
     */
    public static function validate(string $code, float $baseAmount): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') { return ['ok' => false, 'error' => 'Enter a coupon code.']; }

        $c = Database::fetch("SELECT * FROM {p}coupons WHERE code=? AND status='active'", [$code]);
        if (!$c) { return ['ok' => false, 'error' => 'Invalid coupon code.']; }

        $now = date('Y-m-d H:i:s');
        if ($c['starts_at'] && $c['starts_at'] > $now) { return ['ok' => false, 'error' => 'Coupon is not active yet.']; }
        if ($c['ends_at'] && $c['ends_at'] < $now)     { return ['ok' => false, 'error' => 'Coupon has expired.']; }
        if ($c['usage_limit'] !== null && (int)$c['used_count'] >= (int)$c['usage_limit']) {
            return ['ok' => false, 'error' => 'Coupon usage limit reached.'];
        }
        if ((float)$c['min_amount'] > 0 && $baseAmount < (float)$c['min_amount']) {
            return ['ok' => false, 'error' => 'Minimum order of ' . money($c['min_amount']) . ' required.'];
        }

        $discount = $c['type'] === 'percent'
            ? round($baseAmount * (float)$c['value'] / 100, 2)
            : (float)$c['value'];
        if ($c['max_discount'] !== null && $c['max_discount'] > 0) {
            $discount = min($discount, (float)$c['max_discount']);
        }
        $discount = min($discount, $baseAmount); // never exceed the base

        return ['ok' => true, 'coupon' => $c, 'discount' => round($discount, 2)];
    }

    /** Increment usage after a booking is created with the coupon. */
    public static function redeem(int $couponId): void
    {
        Database::run("UPDATE {p}coupons SET used_count = used_count + 1 WHERE id=?", [$couponId]);
    }
}
