<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Booking lifecycle helpers: code generation, confirmation (commission +
 * notifications), and cancellation.
 */
class BookingService
{
    /** Generate the next booking code, e.g. DWK-2026-00184. */
    public static function nextCode(): string
    {
        $year = (int)date('Y');
        $seq = (int)Database::scalar(
            "SELECT COUNT(*) FROM {p}bookings WHERE YEAR(created_at)=?",
            [$year]
        ) + 1;
        // Ensure uniqueness even under races.
        do {
            $code = sprintf('DWK-%d-%05d', $year, $seq);
            $exists = Database::scalar("SELECT COUNT(*) FROM {p}bookings WHERE code=?", [$code]);
            $seq++;
        } while ($exists);
        return $code;
    }

    /**
     * Confirm a booking after (advance) payment: mark confirmed, run the
     * commission engine (Phase 5), and dispatch WhatsApp notifications (3.3).
     * Idempotent — a booking already past pending_payment is left alone.
     */
    public static function confirm(int $bookingId): void
    {
        $booking = Database::fetch("SELECT * FROM {p}bookings WHERE id=?", [$bookingId]);
        if (!$booking || $booking['status'] !== 'pending_payment') {
            return;
        }

        Database::update('bookings', ['status' => 'confirmed'], ['id' => $bookingId]);
        $booking['status'] = 'confirmed';

        // Commission + wallet credit (Phase 5). Guarded so earlier phases work.
        if (class_exists(\App\Services\CommissionEngine::class)) {
            try { \App\Services\CommissionEngine::apply($bookingId); } catch (\Throwable $e) { log_line('app.log', 'commission: ' . $e->getMessage()); }
        }

        self::sendConfirmationMessages($booking);
    }

    /** Build variables and queue the four confirmation messages. */
    public static function sendConfirmationMessages(array $b): void
    {
        $vehicle = Database::fetch("SELECT * FROM {p}vehicles WHERE id=?", [$b['vehicle_id']]);
        $agency  = $b['agency_id'] ? Database::fetch("SELECT * FROM {p}agencies WHERE id=?", [$b['agency_id']]) : null;
        $shop    = $b['shop_id'] ? Database::fetch("SELECT * FROM {p}shops WHERE id=?", [$b['shop_id']]) : null;

        $vars = [
            'customer_name' => $b['customer_name'],
            'booking_code'  => $b['code'],
            'vehicle_name'  => $vehicle['name'] ?? '',
            'pickup_time'   => date('d M Y, h:i A', strtotime($b['pickup_at'])),
            'drop_time'     => date('d M Y, h:i A', strtotime($b['drop_at'])),
            'amount'        => money($b['paid_amount']),
            'balance'       => money($b['balance_amount']),
            'shop_name'     => $shop['name'] ?? 'Direct',
            'agency_mobile' => $agency['mobile'] ?? setting('contact_mobile', ''),
            'map_link'      => setting('map_link', ''),
            'receipt_link'  => base_url('/booking/' . $b['code'] . '/success'),
        ];

        // Customer
        if ($b['customer_mobile']) {
            Whatsapp::notify('booking_confirmed_customer', $b['customer_mobile'], $vars);
        }
        // Agency
        if ($agency && ($agency['whatsapp'] ?? $agency['mobile'] ?? '')) {
            Whatsapp::notify('booking_alert_agency', $agency['whatsapp'] ?: $agency['mobile'], $vars);
        }
        // Shop (commission alert)
        if ($shop && ($shop['whatsapp'] ?? $shop['mobile'] ?? '')) {
            [$todayCommission, $totalCommission] = self::shopCommissionTotals((int)$shop['id']);
            $shopVars = $vars + [
                'today_commission' => money($todayCommission),
                'total_commission' => money($totalCommission),
            ];
            Whatsapp::notify('commission_alert_shop', $shop['whatsapp'] ?: $shop['mobile'], $shopVars);
        }
        // Admin
        if ($adminWa = setting('contact_whatsapp', '')) {
            Whatsapp::notify('booking_alert_admin', $adminWa, $vars);
        }
    }

    /** Today's and lifetime commission for a shop (from the ledger). */
    public static function shopCommissionTotals(int $shopId): array
    {
        $today = (float)Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0)
             FROM {p}commission_ledger WHERE beneficiary_type='shop' AND beneficiary_id=? AND DATE(created_at)=CURDATE()",
            [$shopId]
        );
        $total = (float)Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN entry_type='reversal' THEN -amount ELSE amount END),0)
             FROM {p}commission_ledger WHERE beneficiary_type='shop' AND beneficiary_id=?",
            [$shopId]
        );
        return [$today, $total];
    }
}
