<?php
/**
 * Notification module: list own notifications, mark one as read.
 * Also exposes helpers used by other modules to create notifications.
 */
declare(strict_types=1);

namespace Findly\Notification;

use PDO;
use Findly\Middleware\AuthMiddleware;
use Findly\Utils\Response;

class NotificationController
{
    public static function notify(int $userId, ?int $itemId, string $message): void
    {
        $st = \Database::connect()->prepare(
            'INSERT INTO notification (userId, itemId, message) VALUES (?, ?, ?)'
        );
        $st->execute([$userId, $itemId, $message]);
    }

    public static function notifyItemParties(array $item, string $message): void
    {
        $notified = [];
        $targets = array_unique(array_filter([(int) $item['postedBy'], (int) ($item['loggedByStaff'] ?? 0)]));
        foreach ($targets as $uid) {
            if ($uid > 0 && !in_array($uid, $notified, true)) {
                NotificationController::notify($uid, (int) $item['itemId'], $message);
                $notified[] = $uid;
            }
        }
    }

    public function index(): void
    {
        $user = AuthMiddleware::user();
        $st = \Database::connect()->prepare(
            'SELECT n.*, i.title AS itemTitle
             FROM notification n
             LEFT JOIN item i ON i.itemId = n.itemId
             WHERE n.userId = ?
             ORDER BY n.createdAt DESC
             LIMIT 100'
        );
        $st->execute([(int) $user['userId']]);
        $notifications = $st->fetchAll();
        $unread = 0;
        foreach ($notifications as $n) {
            if ((int) $n['isRead'] === 0) {
                ++$unread;
            }
        }
        Response::ok(['notifications' => $notifications, 'unread' => $unread], '');
    }

    public function markRead(int $id): void
    {
        $user = AuthMiddleware::user();
        $st = \Database::connect()->prepare(
            'UPDATE notification SET isRead = 1 WHERE notificationId = ? AND userId = ?'
        );
        $st->execute([$id, (int) $user['userId']]);
        Response::ok(null, 'Notification marked as read');
    }
}