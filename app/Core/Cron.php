<?php
namespace App\Core;

use App\Services\Whatsapp;

/**
 * Cron tasks, dispatched by cron.php. Each method is defensive and returns a
 * small summary for the cron log.
 */
class Cron
{
    public static function run(): array
    {
        return [
            'whatsapp_sent'   => self::dispatchQueue(),
            'pickup_reminders'=> self::pickupReminders(),
            'return_reminders'=> self::returnReminders(),
            'auto_cancelled'  => self::autoCancelUnpaid(),
            'update_check'    => self::dailyUpdateCheck(),
        ];
    }

    /** Send pending WhatsApp queue messages with throttling + retries. */
    public static function dispatchQueue(int $batch = 15): int
    {
        if (Settings::get('wa_enabled') !== '1') {
            return 0;
        }
        // Respect the daily limit.
        $dailyLimit = (int)Settings::get('wa_daily_limit', 500);
        $sentToday = (int)Database::scalar("SELECT COUNT(*) FROM {p}whatsapp_queue WHERE status='sent' AND DATE(sent_at)=CURDATE()");
        if ($sentToday >= $dailyLimit) {
            return 0;
        }
        $batch = min($batch, $dailyLimit - $sentToday);

        $rows = Database::fetchAll(
            "SELECT * FROM {p}whatsapp_queue WHERE status='pending' AND scheduled_at<=? ORDER BY id ASC LIMIT {$batch}",
            [date('Y-m-d H:i:s')]
        );
        $sent = 0;
        $min = (int)Settings::get('wa_min_delay', 3);
        $max = max($min, (int)Settings::get('wa_max_delay', 8));

        foreach ($rows as $row) {
            $result = Whatsapp::sendNow($row['to_number'], $row['message']);
            if ($result['ok']) {
                Database::update('whatsapp_queue', [
                    'status' => 'sent', 'sent_at' => now(), 'response_json' => (string)$result['response'],
                ], ['id' => $row['id']]);
                Whatsapp::log('out', $row['to_number'], $row['message'], 'sent', $result['response'], $row['template_key']);
                $sent++;
            } else {
                $attempts = (int)$row['attempts'] + 1;
                if ($attempts >= 3) {
                    Database::update('whatsapp_queue', [
                        'status' => 'failed', 'attempts' => $attempts, 'last_error' => substr((string)$result['response'], 0, 255),
                    ], ['id' => $row['id']]);
                    Whatsapp::log('out', $row['to_number'], $row['message'], 'failed', $result['response'], $row['template_key']);
                } else {
                    // Exponential backoff: 2^attempts minutes.
                    $delay = (2 ** $attempts) * 60;
                    Database::update('whatsapp_queue', [
                        'attempts' => $attempts, 'last_error' => substr((string)$result['response'], 0, 255),
                        'scheduled_at' => date('Y-m-d H:i:s', time() + $delay),
                    ], ['id' => $row['id']]);
                }
            }
            // Throttle between sends to protect the sender number.
            if (PHP_SAPI === 'cli') { sleep(random_int($min, $max)); }
        }
        return $sent;
    }

    /** Queue pickup reminders ~2 hours before pickup (once per booking). */
    public static function pickupReminders(): int
    {
        $rows = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name, a.mobile AS agency_mobile
             FROM {p}bookings b LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id LEFT JOIN {p}agencies a ON a.id=b.agency_id
             WHERE b.status='confirmed'
               AND b.pickup_at BETWEEN DATE_ADD(NOW(), INTERVAL 110 MINUTE) AND DATE_ADD(NOW(), INTERVAL 130 MINUTE)
               AND NOT EXISTS (SELECT 1 FROM {p}whatsapp_queue q WHERE q.template_key='pickup_reminder' AND JSON_EXTRACT(q.payload,'$.booking_code')=b.code)"
        );
        foreach ($rows as $b) {
            Whatsapp::notify('pickup_reminder', $b['customer_mobile'], [
                'booking_code' => $b['code'], 'vehicle_name' => $b['vehicle_name'],
                'pickup_time' => date('d M h:iA', strtotime($b['pickup_at'])), 'agency_mobile' => $b['agency_mobile'] ?? '',
            ]);
        }
        return count($rows);
    }

    /** Queue return reminders ~1 hour before drop-off. */
    public static function returnReminders(): int
    {
        $rows = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name FROM {p}bookings b LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             WHERE b.status IN ('confirmed','picked_up')
               AND b.drop_at BETWEEN DATE_ADD(NOW(), INTERVAL 50 MINUTE) AND DATE_ADD(NOW(), INTERVAL 70 MINUTE)
               AND NOT EXISTS (SELECT 1 FROM {p}whatsapp_queue q WHERE q.template_key='return_reminder' AND JSON_EXTRACT(q.payload,'$.booking_code')=b.code)"
        );
        foreach ($rows as $b) {
            Whatsapp::notify('return_reminder', $b['customer_mobile'], [
                'booking_code' => $b['code'], 'vehicle_name' => $b['vehicle_name'],
                'drop_time' => date('d M h:iA', strtotime($b['drop_at'])),
            ]);
        }
        return count($rows);
    }

    /** Auto-cancel unpaid bookings older than the configured timeout. */
    public static function autoCancelUnpaid(): int
    {
        $minutes = max(1, (int)Settings::get('auto_cancel_minutes', 20));
        $stale = Database::fetchAll(
            "SELECT id FROM {p}bookings WHERE status='pending_payment' AND payment_status IN ('unpaid')
             AND created_at < DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)"
        );
        foreach ($stale as $b) {
            Database::update('bookings', ['status' => 'cancelled'], ['id' => $b['id']]);
        }
        return count($stale);
    }

    /** Daily GitHub update check (Phase 8). */
    public static function dailyUpdateCheck(): string
    {
        if (Settings::get('github_auto_check') !== '1') {
            return 'disabled';
        }
        if (class_exists(\App\Services\Updater::class)) {
            try {
                $info = \App\Services\Updater::checkForUpdate();
                if (!empty($info['update_available']) && ($adminWa = Settings::get('contact_whatsapp'))) {
                    Whatsapp::queue($adminWa, 'A new Dwarka Rental update is available: ' . ($info['message'] ?? ''));
                }
                return !empty($info['update_available']) ? 'update_available' : 'up_to_date';
            } catch (\Throwable $e) {
                return 'error';
            }
        }
        return 'no_updater';
    }
}
