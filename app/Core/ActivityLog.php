<?php
namespace App\Core;

/**
 * Audit trail for admin/staff actions (who changed what, old vs new).
 */
class ActivityLog
{
    public static function record(string $action, string $entity = '', $entityId = null, array $old = [], array $new = []): void
    {
        try {
            Database::insert('activity_log', [
                'user_id'    => Auth::id(),
                'role'       => Auth::role(),
                'action'     => substr($action, 0, 190),
                'entity'     => substr($entity, 0, 190),
                'entity_id'  => $entityId !== null ? (string)$entityId : null,
                'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => Request::ip(),
                'user_agent' => Request::userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            log_line('app.log', 'ActivityLog failed: ' . $e->getMessage());
        }
    }
}
