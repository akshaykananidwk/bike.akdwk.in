<?php
namespace App\Services;

use App\Core\Database;

/**
 * Vehicle availability engine. A vehicle has N identical units; a time slot is
 * available if the number of overlapping active bookings is less than N and the
 * slot is not inside a maintenance block.
 */
class Availability
{
    /** Booking statuses that occupy a unit. */
    private const OCCUPYING = "('confirmed','picked_up','pending_payment')";

    public static function isAvailable(int $vehicleId, string $pickup, string $drop, ?int $excludeBooking = null): bool
    {
        return self::availableUnits($vehicleId, $pickup, $drop, $excludeBooking) > 0;
    }

    /** How many units are free for the given window. */
    public static function availableUnits(int $vehicleId, string $pickup, string $drop, ?int $excludeBooking = null): int
    {
        $vehicle = Database::fetch("SELECT units, status FROM {p}vehicles WHERE id=?", [$vehicleId]);
        if (!$vehicle || $vehicle['status'] !== 'active') {
            return 0;
        }
        $units = max(1, (int)$vehicle['units']);

        // Maintenance blocks fully remove availability.
        $blocked = Database::scalar(
            "SELECT COUNT(*) FROM {p}vehicle_blocks
             WHERE vehicle_id=? AND start_date < ? AND end_date > ?",
            [$vehicleId, $drop, $pickup]
        );
        if ($blocked > 0) {
            return 0;
        }

        // Overlap rule: existing.pickup < new.drop AND existing.drop > new.pickup
        $params = [$vehicleId, $drop, $pickup];
        $exclude = '';
        if ($excludeBooking !== null) {
            $exclude = ' AND id <> ?';
            $params[] = $excludeBooking;
        }
        $overlaps = (int)Database::scalar(
            "SELECT COUNT(*) FROM {p}bookings
             WHERE vehicle_id=? AND status IN " . self::OCCUPYING . "
               AND pickup_at < ? AND drop_at > ?" . $exclude,
            $params
        );

        return max(0, $units - $overlaps);
    }
}
