<?php
namespace App\Services;

use App\Core\Settings;

/**
 * Rental price calculation. Uses hourly/daily/weekly slabs, GST, deposit and
 * the configured advance-payment percentage.
 */
class Pricing
{
    /**
     * @return array{
     *   hours:float, days:int, base:float, gst:float, deposit:float,
     *   total:float, advance:float, balance:float, gst_percent:float, advance_percent:float
     * }
     */
    /**
     * @param array $addons ['doorstep'=>bool, 'insurance'=>bool, 'credit'=>float]
     */
    public static function quote(array $vehicle, string $pickup, string $drop, float $discount = 0.0, array $addons = []): array
    {
        $start = strtotime($pickup);
        $end   = strtotime($drop);
        $minutes = max(0, ($end - $start) / 60);
        $totalHours = max(1, (int)ceil($minutes / 60));

        $priceHour  = (float)$vehicle['price_hour'];
        $priceDay   = (float)$vehicle['price_day'];
        $priceWeek  = (float)$vehicle['price_week'];
        $deposit    = (float)$vehicle['deposit'];

        $days = intdiv($totalHours, 24);
        $rem  = $totalHours % 24;

        $base = 0.0;
        // Weekly optimisation.
        if ($priceWeek > 0 && $days >= 7) {
            $weeks = intdiv($days, 7);
            $base += $weeks * $priceWeek;
            $days -= $weeks * 7;
        }
        $base += $days * $priceDay;

        if ($rem > 0) {
            if ($priceHour > 0) {
                $hourCost = $rem * $priceHour;
                // Never charge more than a full day for the leftover hours.
                $base += ($priceDay > 0) ? min($hourCost, $priceDay) : $hourCost;
            } else {
                // No hourly rate — charge a full day for any partial day.
                $base += $priceDay;
            }
        }
        if ($base <= 0) {
            $base = $priceDay;
        }

        $rawBase = $base;
        $base = max(0, $base - $discount);

        $gstPercent = (float)Settings::get('gst_percent', 0);
        $gst = round($base * $gstPercent / 100, 2);

        // --- Optional add-ons -------------------------------------------------
        // Doorstep delivery: free above a configurable order value.
        $deliveryCharge = 0.0;
        if (!empty($addons['doorstep']) && Settings::get('delivery_enabled', '1') === '1') {
            $freeAbove = (float)Settings::get('delivery_free_above', 0);
            $deliveryCharge = ($freeAbove > 0 && $base >= $freeAbove)
                ? 0.0
                : (float)Settings::get('delivery_charge', 0);
        }
        // Damage protection / insurance.
        $insurance = 0.0;
        if (!empty($addons['insurance']) && Settings::get('insurance_enabled', '1') === '1') {
            $insurance = (float)Settings::get('insurance_amount', 0);
        }

        $advancePercent = (float)Settings::get('advance_percent', 100);
        $rentalWithTax = $base + $gst + $deliveryCharge + $insurance;

        // Customer referral/loyalty credit is applied last, never below zero.
        $credit = min(max(0.0, (float)($addons['credit'] ?? 0)), $rentalWithTax);
        $rentalWithTax = round($rentalWithTax - $credit, 2);

        // Deposit (refundable) is collected online along with the advance.
        $advance = round($rentalWithTax * $advancePercent / 100, 2) + $deposit;
        $total   = round($rentalWithTax + $deposit, 2);
        $balance = round($total - $advance, 2);

        return [
            'hours'          => (float)$totalHours,
            'days'           => (int)ceil($totalHours / 24),
            'base'           => round($base, 2),
            'raw_base'       => round($rawBase, 2),
            'gst'            => $gst,
            'gst_percent'    => $gstPercent,
            'deposit'        => round($deposit, 2),
            'discount'       => round($discount, 2),
            'delivery'       => round($deliveryCharge, 2),
            'insurance'      => round($insurance, 2),
            'credit'         => round($credit, 2),
            'total'          => $total,
            'advance'        => round($advance, 2),
            'advance_percent'=> $advancePercent,
            'balance'        => max(0, $balance),
        ];
    }
}
