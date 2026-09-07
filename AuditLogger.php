<?php
/**
 * Audit trail: one row per state-changing action.
 */
declare(strict_types=1);

namespace Findly\Audit;

use PDO;

class AuditLogger
{
    public static function log(
        int $userId,
        ?int $itemId,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $details = null
    ): void {
        $st = \Database::connect()->prepare(
            'INSERT INTO audit_log (userId, itemId, action, oldStatus, newStatus, details)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$userId, $itemId, $action, $oldStatus, $newStatus, $details]);
    }

    public static function recent(int $limit = 15): array
    {
        $limit = max(1, min(100, (int) $limit));
        $st = \Database::connect()->prepare(
            "SELECT a.*, u.name AS userName, i.title AS itemTitle
             FROM audit_log a
             JOIN user u ON u.userId = a.userId
             LEFT JOIN item i ON i.itemId = a.itemId
             ORDER BY a.timestamp DESC
             LIMIT $limit"
        );
        $st->execute();
        return $st->fetchAll();
    }
}