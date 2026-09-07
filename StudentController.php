<?php
/**
 * Student module: report lost items and a small dashboard summary.
 * (Browsing found items / my reports use the shared GET /api/items endpoint.)
 */
declare(strict_types=1);

namespace Findly\Student;

use PDO;
use Findly\Item\ItemController;
use Findly\Middleware\AuthMiddleware;
use Findly\Utils\Response;

class StudentController
{
    public function reportLost(): void
    {
        $user = AuthMiddleware::user();
        ItemController::createItem($user, 'LOST');
    }

    public function summary(): void
    {
        $user = AuthMiddleware::user();
        $db = \Database::connect();

        $st = $db->prepare(
            "SELECT status, COUNT(*) AS total FROM item
             WHERE postedBy = ? AND itemType = 'LOST'
             GROUP BY status"
        );
        $st->execute([(int) $user['userId']]);
        $byStatus = ['PENDING' => 0, 'ACTIVE' => 0, 'REJECTED' => 0];
        foreach ($st->fetchAll() as $row) {
            if (isset($byStatus[$row['status']])) {
                $byStatus[$row['status']] = (int) $row['total'];
            }
        }

        $st = $db->prepare(
            "SELECT COUNT(*) FROM item WHERE itemType = 'FOUND' AND status = 'ACTIVE'"
        );
        $st->execute();
        $activeFound = (int) $st->fetchColumn();

        Response::ok([
            'myLostByStatus' => $byStatus,
            'totalLostReports' => array_sum($byStatus),
            'activeFoundItems' => $activeFound,
        ], '');
    }
}