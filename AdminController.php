<?php
/**
 * Admin module: moderation, user management, dashboard statistics.
 */
declare(strict_types=1);

namespace Findly\Admin;

use Findly\Audit\AuditLogger;
use Findly\Item\ItemRepository;
use Findly\Middleware\AuthMiddleware;
use Findly\Notification\NotificationController;
use Findly\User\UserRepository;
use Findly\Utils\Request;
use Findly\Utils\Response;

class AdminController
{
    public function moderate(int $id): void
    {
        $user = AuthMiddleware::requireRole('ADMIN');
        $item = ItemRepository::findById($id);
        if ($item === null) {
            Response::error('Item not found', 404);
        }
        if ($item['status'] !== 'PENDING') {
            Response::error('Only pending items can be moderated', 400);
        }

        $data = Request::json();
        $decision = strtoupper((string) ($data['decision'] ?? ''));
        if (!in_array($decision, ['APPROVE', 'REJECT'], true)) {
            Response::error('Decision must be APPROVE or REJECT', 422);
        }

        $newStatus = $decision === 'APPROVE' ? 'ACTIVE' : 'REJECTED';
        ItemRepository::setStatus($id, $newStatus);
        AuditLogger::log((int) $user['userId'], $id, 'MODERATE', 'PENDING', $newStatus, 'Moderated: ' . $decision);

        $verb = $decision === 'APPROVE' ? 'approved' : 'rejected';
        $message = 'Your ' . strtolower($item['itemType']) . ' item "' . $item['title'] . '" has been ' . $verb . '.';
        NotificationController::notify((int) $item['postedBy'], $id, $message);
        if ((int) $item['loggedByStaff'] > 0 && (int) $item['loggedByStaff'] !== (int) $item['postedBy']) {
            NotificationController::notify((int) $item['loggedByStaff'], $id, 'Found item "' . $item['title'] . '" has been ' . $verb . '.');
        }

        Response::ok(['itemId' => $id, 'newStatus' => $newStatus], 'Item ' . $verb);
    }

    public function users(): void
    {
        AuthMiddleware::requireRole('ADMIN');
        Response::ok(['users' => UserRepository::all()], '');
    }

    public function setUserStatus(int $id): void
    {
        $user = AuthMiddleware::requireRole('ADMIN');
        if ((int) $id === (int) $user['userId']) {
            Response::error('You cannot change your own account status', 400);
        }

        $data = Request::json();
        $status = strtoupper((string) ($data['status'] ?? ''));
        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'SUSPENDED'], true)) {
            Response::error('Status must be ACTIVE, INACTIVE or SUSPENDED', 422);
        }

        $target = UserRepository::byId($id);
        if ($target === null) {
            Response::error('User not found', 404);
        }

        UserRepository::updateStatus($id, $status);
        AuditLogger::log((int) $user['userId'], null, 'USER_STATUS_CHANGE', $target['accountStatus'], $status, 'User status changed for ' . $target['email']);
        Response::ok(['userId' => $id, 'status' => $status], 'User status updated');
    }

    public function dashboard(): void
    {
        AuthMiddleware::requireRole('ADMIN');
        $sinceWeek = date('Y-m-d H:i:s', strtotime('-7 days'));
        Response::ok([
            'usersByRole'     => UserRepository::countByRole(),
            'totalUsers'      => UserRepository::totalUsers(),
            'itemsByTypeStatus'=> ItemRepository::countByTypeStatus(),
            'pendingApprovals' => ItemRepository::countPending(),
            'pendingHandovers' => ItemRepository::countClaimed(),
            'resolvedCount'    => ItemRepository::countResolved(),
            'resolvedThisWeek' => ItemRepository::countResolvedSince($sinceWeek),
            'activeItems'      => ItemRepository::countActive(),
            'recentActivity'   => AuditLogger::recent(12),
        ], '');
    }
}