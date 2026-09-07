<?php
/**
 * Staff module: log found items, custody, claim, recipient, resolve, dashboard.
 * Admin inherits these capabilities (role check >= STAFF).
 */
declare(strict_types=1);

namespace Findly\Staff;

use Findly\Audit\AuditLogger;
use Findly\Item\ItemController;
use Findly\Item\ItemRepository;
use Findly\Middleware\AuthMiddleware;
use Findly\Notification\NotificationController;
use Findly\Utils\Request;
use Findly\Utils\Response;
use Findly\Utils\Validator;

class StaffController
{
    public function logFound(): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        ItemController::createItem($user, 'FOUND');
    }

    public function custody(int $id): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        $item = ItemRepository::findById($id);
        if ($item === null || $item['itemType'] !== 'FOUND') {
            Response::error('Found item not found', 404);
        }
        self::assertInvolved($user, $item);

        $data = Request::json();
        $v = new Validator($data);
        $v->required('custodyLocation')->length('custodyLocation', 3, 200);
        $v->required('custodyStatus')->enum('custodyStatus', ['IN_CUSTODY', 'MOVED', 'HANDED_OVER']);
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }
        if (in_array($item['status'], ['RESOLVED', 'REJECTED'], true)) {
            Response::error('This item can no longer be moved', 400);
        }

        ItemRepository::updateCustody($id, trim((string) $data['custodyLocation']), (string) $data['custodyStatus']);
        AuditLogger::log((int) $user['userId'], $id, 'UPDATE_CUSTODY', $item['status'], $item['status'], 'Custody updated to ' . $data['custodyStatus']);
        Response::ok(['itemId' => $id], 'Custody location updated');
    }

    public function claim(int $id): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        $item = ItemRepository::findById($id);
        if ($item === null || $item['itemType'] !== 'FOUND') {
            Response::error('Found item not found', 404);
        }
        self::assertInvolved($user, $item);
        if ($item['status'] !== 'ACTIVE') {
            Response::error('Only active items can be marked as claimed', 400);
        }

        ItemRepository::setClaimed($id);
        AuditLogger::log((int) $user['userId'], $id, 'CLAIM', 'ACTIVE', 'CLAIMED', 'Item claimed after in-person verification');
        NotificationController::notifyItemParties($item, 'A found item "' . $item['title'] . '" has been marked as claimed.');
        Response::ok(['itemId' => $id], 'Item marked as claimed. Please record the recipient details.');
    }

    public function recipient(int $id): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        $item = ItemRepository::findById($id);
        if ($item === null || $item['itemType'] !== 'FOUND') {
            Response::error('Found item not found', 404);
        }
        self::assertInvolved($user, $item);
        if ($item['status'] !== 'CLAIMED') {
            Response::error('Recipient details can only be recorded for a CLAIMED item', 400);
        }

        $data = Request::json();
        $v = new Validator($data);
        $v->required('recipientName')->length('recipientName', 2, 100);
        $v->required('recipientContactNo')->phone('recipientContactNo');
        $v->required('recipientEnrollmentNo')->length('recipientEnrollmentNo', 3, 30);
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        ItemRepository::setRecipient(
            $id,
            trim((string) $data['recipientName']),
            trim((string) $data['recipientContactNo']),
            trim((string) $data['recipientEnrollmentNo'])
        );
        AuditLogger::log((int) $user['userId'], $id, 'RECORD_RECIPIENT', 'CLAIMED', 'CLAIMED', 'Recipient details recorded for ' . trim((string) $data['recipientName']));
        Response::ok(['itemId' => $id], 'Recipient details recorded. The item is ready to be resolved.');
    }

    public function resolve(int $id): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        $item = ItemRepository::findById($id);
        if ($item === null || $item['itemType'] !== 'FOUND') {
            Response::error('Found item not found', 404);
        }
        self::assertInvolved($user, $item);
        if ($item['status'] !== 'CLAIMED') {
            Response::error('Item must be CLAIMED and have a recipient before resolving', 400);
        }
        if (empty($item['recipientName']) || empty($item['recipientContactNo'])) {
            Response::error('Recipient details must be recorded before resolving', 400);
        }

        ItemRepository::setResolved($id);
        AuditLogger::log((int) $user['userId'], $id, 'RESOLVE', 'CLAIMED', 'RESOLVED', 'Handover completed');
        NotificationController::notifyItemParties($item, 'Found item "' . $item['title'] . '" has been resolved after handover.');
        Response::ok(['itemId' => $id], 'Item marked as resolved. Handover complete.');
    }

    public function dashboard(): void
    {
        $user = AuthMiddleware::requireRole('STAFF');
        $sinceWeek = date('Y-m-d H:i:s', strtotime('-7 days'));
        Response::ok([
            'pendingFoundLogs'   => ItemRepository::countForStaff((int) $user['userId'], 'FOUND', 'PENDING'),
            'activeFoundItems'   => ItemRepository::countForStaff((int) $user['userId'], 'FOUND', 'ACTIVE'),
            'itemsInCustody'     => ItemRepository::countInCustodyForStaff((int) $user['userId']),
            'resolvedThisWeek'   => ItemRepository::countResolvedForStaff((int) $user['userId'], $sinceWeek),
        ], '');
    }

    private static function assertInvolved(array $user, array $item): void
    {
        // Admins may handle any item; staff only items they logged.
        if ($user['role'] === 'ADMIN') {
            return;
        }
        if ((int) $item['loggedByStaff'] !== (int) $user['userId']) {
            Response::forbidden();
        }
    }
}