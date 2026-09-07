<?php
/**
 * Verifies the logged-in session and returns the current user row straight
 * from the database (so role/suspension changes take effect immediately).
 */
declare(strict_types=1);

namespace Findly\Middleware;

use Findly\Auth\SessionManager;
use Findly\User\UserRepository;
use Findly\Utils\Response;

class AuthMiddleware
{
    public static function user(): array
    {
        SessionManager::init();
        $id = SessionManager::userId();
        if ($id === null) {
            Response::unauthorized('You must be logged in');
        }
        $user = UserRepository::byId($id);
        if ($user === null) {
            SessionManager::logout();
            Response::unauthorized('You must be logged in');
        }
        if ($user['accountStatus'] !== 'ACTIVE') {
            SessionManager::logout();
            $message = $user['accountStatus'] === 'SUSPENDED'
                ? 'Your account has been suspended'
                : 'You must be logged in';
            Response::unauthorized($message);
        }
        return $user;
    }

    /**
     * Verifies the current user's role is at least the required role.
     * Hierarchy: STUDENT < STAFF < ADMIN.
     */
    public static function requireRole(string $minRole): array
    {
        $user = self::user();
        $rankMap = ['STUDENT' => 1, 'STAFF' => 2, 'ADMIN' => 3];
        if (($rankMap[$user['role']] ?? 0) < ($rankMap[$minRole] ?? PHP_INT_MAX)) {
            Response::forbidden();
        }
        return $user;
    }
}