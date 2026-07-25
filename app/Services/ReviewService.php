<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * Customer reviews. A review can only be left through a one-time token that is
 * generated for a completed booking, so every rating on the site belongs to a
 * real, paid ride — no invented star ratings.
 */
class ReviewService
{
    /** Ensure a booking has a review token and return it. */
    public static function tokenFor(int $bookingId): string
    {
        $b = Database::fetch("SELECT review_token FROM {p}bookings WHERE id=?", [$bookingId]);
        if ($b && !empty($b['review_token'])) {
            return (string)$b['review_token'];
        }
        $token = bin2hex(random_bytes(16));
        Database::update('bookings', ['review_token' => $token], ['id' => $bookingId]);
        return $token;
    }

    public static function bookingByToken(string $token): ?array
    {
        if ($token === '') { return null; }
        return Database::fetch(
            "SELECT b.*, v.name AS vehicle_name, p.name AS package_name
             FROM {p}bookings b
             LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             LEFT JOIN {p}packages p ON p.id=b.package_id
             WHERE b.review_token=?",
            [$token]
        );
    }

    public static function existingFor(int $bookingId): ?array
    {
        return Database::fetch("SELECT * FROM {p}reviews WHERE booking_id=?", [$bookingId]);
    }

    /** Save a review for a booking (one per booking). */
    public static function submit(array $booking, int $rating, string $comment): array
    {
        $rating = max(1, min(5, $rating));
        if (self::existingFor((int)$booking['id'])) {
            return ['ok' => false, 'error' => 'You have already reviewed this booking. Thank you!'];
        }
        $auto = Settings::get('reviews_auto_approve', '0') === '1';
        Database::insert('reviews', [
            'booking_id'      => $booking['id'],
            'vehicle_id'      => $booking['vehicle_id'] ?: null,
            'package_id'      => $booking['package_id'] ?: null,
            'agency_id'       => $booking['agency_id'] ?: null,
            'customer_name'   => $booking['customer_name'],
            'customer_mobile' => $booking['customer_mobile'],
            'rating'          => $rating,
            'comment'         => trim($comment) ?: null,
            'status'          => $auto ? 'approved' : 'pending',
        ]);
        return ['ok' => true, 'auto' => $auto, 'rating' => $rating];
    }

    /** Aggregate rating for a vehicle (approved reviews only). */
    public static function vehicleRating(int $vehicleId): array
    {
        $r = Database::fetch(
            "SELECT AVG(rating) avg_rating, COUNT(*) cnt FROM {p}reviews WHERE vehicle_id=? AND status='approved'",
            [$vehicleId]
        );
        return ['avg' => round((float)($r['avg_rating'] ?? 0), 1), 'count' => (int)($r['cnt'] ?? 0)];
    }

    /** Site-wide aggregate (used for the homepage / LocalBusiness schema). */
    public static function siteRating(): array
    {
        $r = Database::fetch("SELECT AVG(rating) avg_rating, COUNT(*) cnt FROM {p}reviews WHERE status='approved'");
        return ['avg' => round((float)($r['avg_rating'] ?? 0), 1), 'count' => (int)($r['cnt'] ?? 0)];
    }

    /**
     * Queue a WhatsApp review request for rides that finished recently.
     * Called by cron. Returns how many were sent.
     */
    public static function requestPending(int $limit = 20): int
    {
        if (Settings::get('reviews_enabled', '1') !== '1') { return 0; }

        $rows = Database::fetchAll(
            "SELECT b.*, v.name AS vehicle_name, p.name AS package_name
             FROM {p}bookings b
             LEFT JOIN {p}vehicles v ON v.id=b.vehicle_id
             LEFT JOIN {p}packages p ON p.id=b.package_id
             WHERE b.status IN ('returned','completed')
               AND b.review_requested_at IS NULL
               AND b.customer_mobile IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM {p}reviews r WHERE r.booking_id=b.id)
             ORDER BY b.id DESC LIMIT {$limit}"
        );

        foreach ($rows as $b) {
            $token = self::tokenFor((int)$b['id']);
            Whatsapp::notify('review_request', $b['customer_mobile'], [
                'customer_name' => $b['customer_name'],
                'booking_code'  => $b['code'],
                'vehicle_name'  => $b['vehicle_name'] ?: ($b['package_name'] ?: 'ride'),
                'receipt_link'  => base_url('/review/' . $token),
            ]);
            Database::update('bookings', ['review_requested_at' => now()], ['id' => $b['id']]);
        }
        return count($rows);
    }
}
