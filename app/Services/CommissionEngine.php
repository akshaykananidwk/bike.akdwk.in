<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Commission engine (the money logic).
 *
 * Rate resolution priority for the SHOP referral commission:
 *   vehicle-level -> agency-level -> shop-level -> global default
 * (first entity whose commission_type is not 'inherit' wins).
 *
 * On confirmation we create commission_ledger rows for the shop, the platform,
 * and the agency payable, and credit the shop + agency wallets atomically.
 * Commission is computed on the base rental only (configurable) — never on the
 * deposit, and taxes only if commission_on_base_only is disabled.
 */
class CommissionEngine
{
    /** Apply commission for a confirmed booking (idempotent). */
    public static function apply(int $bookingId): void
    {
        $b = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$bookingId]);
        if (!$b) { return; }

        // Idempotency: skip if a ledger already exists for this booking.
        if (Database::scalar("SELECT COUNT(*) FROM {p}commission_ledger WHERE booking_id=?", [$bookingId]) > 0) {
            return;
        }

        $vehicle = $b['vehicle_id'] ? Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$b['vehicle_id']]) : null;
        $agency  = $b['agency_id']  ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$b['agency_id']]) : null;
        $shop    = $b['shop_id']    ? Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$b['shop_id']]) : null;

        $onBaseOnly = Settings::get('commission_on_base_only', '1') === '1';
        $base = (float)$b['base_amount'] + ($onBaseOnly ? 0 : (float)$b['tax_amount']);
        $base = max(0, $base);

        $globalType  = Settings::get('commission_type', 'percent');
        $globalValue = (float)Settings::get('commission_value', 10);

        // Resolve the shop referral rate by priority.
        [$rateType, $rateValue] = self::resolveRate([$vehicle, $agency, $shop], $globalType, $globalValue);
        $referred = $shop !== null;
        $shopCommission = $referred ? self::applyRate($rateType, $rateValue, $base) : 0.0;

        // Agency settlement rate = platform's cut from the agency.
        [$agType, $agValue] = $agency && $agency['commission_type'] !== 'inherit'
            ? [$agency['commission_type'], (float)$agency['commission_value']]
            : [$globalType, $globalValue];
        $agencyCommission = $agency ? self::applyRate($agType, $agValue, $base) : self::applyRate($globalType, $globalValue, $base);

        // Platform keeps its cut minus what it pays the referring shop.
        $platformNet = round($agencyCommission - $shopCommission, 2);
        $agencyPayable = round($base - $agencyCommission, 2);

        Database::transaction(function () use ($bookingId, $shop, $agency, $base, $rateType, $rateValue, $shopCommission, $platformNet, $agencyPayable, $agType, $agValue) {
            // Shop commission
            if ($shop && $shopCommission > 0) {
                Database::insert('commission_ledger', [
                    'booking_id' => $bookingId, 'beneficiary_type' => 'shop', 'beneficiary_id' => $shop['id'],
                    'entry_type' => 'credit', 'base_amount' => $base, 'rate_type' => $rateType,
                    'rate_value' => $rateValue, 'amount' => $shopCommission, 'note' => 'Referral commission',
                ]);
                WalletService::credit('shop', (int)$shop['id'], $shopCommission, 'commission', $bookingId, 'Booking commission');
            }
            // Platform (informational; the house has no wallet)
            Database::insert('commission_ledger', [
                'booking_id' => $bookingId, 'beneficiary_type' => 'platform', 'beneficiary_id' => null,
                'entry_type' => 'credit', 'base_amount' => $base, 'rate_type' => null, 'rate_value' => null,
                'amount' => $platformNet, 'note' => 'Platform margin',
            ]);
            // Agency payable
            if ($agency) {
                Database::insert('commission_ledger', [
                    'booking_id' => $bookingId, 'beneficiary_type' => 'agency', 'beneficiary_id' => $agency['id'],
                    'entry_type' => 'credit', 'base_amount' => $base, 'rate_type' => $agType, 'rate_value' => $agValue,
                    'amount' => $agencyPayable, 'note' => 'Agency settlement',
                ]);
                if ($agencyPayable > 0) {
                    WalletService::credit('agency', (int)$agency['id'], $agencyPayable, 'settlement', $bookingId, 'Booking settlement');
                }
            }
        });
    }

    /** Reverse all commission for a cancelled/refunded booking (idempotent). */
    public static function reverse(int $bookingId): void
    {
        $rows = Database::fetchAll(
            "SELECT * FROM {p}commission_ledger WHERE booking_id=? AND entry_type='credit'",
            [$bookingId]
        );
        if (!$rows) { return; }
        // Skip if already reversed.
        if (Database::scalar("SELECT COUNT(*) FROM {p}commission_ledger WHERE booking_id=? AND entry_type='reversal'", [$bookingId]) > 0) {
            return;
        }
        Database::transaction(function () use ($rows, $bookingId) {
            foreach ($rows as $r) {
                Database::insert('commission_ledger', [
                    'booking_id' => $bookingId, 'beneficiary_type' => $r['beneficiary_type'], 'beneficiary_id' => $r['beneficiary_id'],
                    'entry_type' => 'reversal', 'base_amount' => $r['base_amount'], 'rate_type' => $r['rate_type'],
                    'rate_value' => $r['rate_value'], 'amount' => $r['amount'], 'note' => 'Reversal (cancellation)',
                ]);
                if ($r['beneficiary_type'] === 'shop' && $r['beneficiary_id']) {
                    WalletService::reverse('shop', (int)$r['beneficiary_id'], (float)$r['amount'], 'commission', $bookingId, 'Cancelled booking');
                } elseif ($r['beneficiary_type'] === 'agency' && $r['beneficiary_id']) {
                    WalletService::reverse('agency', (int)$r['beneficiary_id'], (float)$r['amount'], 'settlement', $bookingId, 'Cancelled booking');
                }
            }
        });
    }

    /** Walk entities in priority order; first non-inherit wins, else global. */
    private static function resolveRate(array $entities, string $globalType, float $globalValue): array
    {
        foreach ($entities as $e) {
            if ($e && ($e['commission_type'] ?? 'inherit') !== 'inherit') {
                return [$e['commission_type'], (float)$e['commission_value']];
            }
        }
        return [$globalType, $globalValue];
    }

    private static function applyRate(string $type, float $value, float $base): float
    {
        return $type === 'fixed' ? round($value, 2) : round($base * $value / 100, 2);
    }

    /** Audit tool: recompute totals from the ledger for reconciliation. */
    public static function audit(): array
    {
        return [
            'shop_credits'     => (float)Database::scalar("SELECT COALESCE(SUM(amount),0) FROM {p}commission_ledger WHERE beneficiary_type='shop' AND entry_type='credit'"),
            'shop_reversals'   => (float)Database::scalar("SELECT COALESCE(SUM(amount),0) FROM {p}commission_ledger WHERE beneficiary_type='shop' AND entry_type='reversal'"),
            'platform_margin'  => (float)Database::scalar("SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0) FROM {p}commission_ledger WHERE beneficiary_type='platform'"),
            'agency_payable'   => (float)Database::scalar("SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0) FROM {p}commission_ledger WHERE beneficiary_type='agency'"),
        ];
    }
}
