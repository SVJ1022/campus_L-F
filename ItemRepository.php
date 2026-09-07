<?php
/**
 * Item data access and business transitions.
 */
declare(strict_types=1);

namespace Findly\Item;

use PDO;

class ItemRepository
{
    private const BASE_SELECT = 'SELECT i.*, c.categoryName, pu.name AS postedByName';

    private static function db(): PDO
    {
        return \Database::connect();
    }

    public static function findById(int $id): ?array
    {
        $sql = self::BASE_SELECT . ", lu.name AS loggedByName
                FROM item i
                JOIN category c ON c.categoryId = i.categoryId
                JOIN user pu ON pu.userId = i.postedBy
                LEFT JOIN user lu ON lu.userId = i.loggedByStaff
                WHERE i.itemId = ?";
        $st = self::db()->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Role-aware search with combined filters:
     * type, status, category, date, location (LIKE), q (keyword over title/description).
     */
    public static function search(array $f, array $currentUser): array
    {
        $role = $currentUser['role'];
        $where = [];
        $params = [];

        if ($role === 'STUDENT') {
            $where[] = "( (i.itemType = 'FOUND' AND i.status = 'ACTIVE') OR (i.itemType = 'LOST' AND i.postedBy = ?) )";
            $params[] = (int) $currentUser['userId'];
        } elseif ($role === 'STAFF') {
            $where[] = "( (i.itemType = 'FOUND' AND (i.loggedByStaff = ? OR i.status = 'ACTIVE')) OR (i.itemType = 'LOST' AND i.postedBy = ?) )";
            $params[] = (int) $currentUser['userId'];
            $params[] = (int) $currentUser['userId'];
        }

        if (!empty($f['type'])) {
            $where[] = 'i.itemType = ?';
            $params[] = (string) $f['type'];
        }
        if (!empty($f['status'])) {
            $where[] = 'i.status = ?';
            $params[] = (string) $f['status'];
        }
        if (!empty($f['category']) && ctype_digit((string) $f['category'])) {
            $where[] = 'i.categoryId = ?';
            $params[] = (int) $f['category'];
        }
        if (!empty($f['date'])) {
            $where[] = 'i.itemDate = ?';
            $params[] = (string) $f['date'];
        }
        if (!empty($f['location'])) {
            $where[] = 'i.location LIKE ?';
            $params[] = '%' . (string) $f['location'] . '%';
        }
        if (!empty($f['q'])) {
            $where[] = '(i.title LIKE ? OR i.description LIKE ?)';
            $q = '%' . (string) $f['q'] . '%';
            $params[] = $q;
            $params[] = $q;
        }

        $sql = self::BASE_SELECT . '
                FROM item i
                JOIN category c ON c.categoryId = i.categoryId
                JOIN user pu ON pu.userId = i.postedBy
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY i.createdAt DESC';

        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function create(array $d): int
    {
        $st = self::db()->prepare(
            'INSERT INTO item (title, description, itemType, categoryId, location, itemDate,
                               imageUrl, custodyLocation, custodyStatus, status, postedBy, loggedByStaff)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $d['title'],
            $d['description'],
            $d['itemType'],
            (int) $d['categoryId'],
            $d['location'],
            $d['itemDate'],
            $d['imageUrl'] ?? null,
            $d['custodyLocation'] ?? null,
            $d['custodyStatus'] ?? null,
            $d['status'],
            (int) $d['postedBy'],
            isset($d['loggedByStaff']) ? (int) $d['loggedByStaff'] : null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function updateFields(int $id, array $fields): void
    {
        $sets = [];
        $params = [];
        foreach ($fields as $col => $val) {
            $sets[] = "$col = ?";
            $params[] = $val;
        }
        $params[] = (int) $id;
        $st = self::db()->prepare('UPDATE item SET ' . implode(', ', $sets) . ' WHERE itemId = ?');
        $st->execute($params);
    }

    public static function delete(int $id): bool
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM notification WHERE itemId = ?')->execute([$id]);
            $db->prepare('DELETE FROM audit_log WHERE itemId = ?')->execute([$id]);
            $st = $db->prepare('DELETE FROM item WHERE itemId = ?');
            $st->execute([$id]);
            $db->commit();
            return $st->rowCount() > 0;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function setStatus(int $id, string $status): void
    {
        $st = self::db()->prepare('UPDATE item SET status = ? WHERE itemId = ?');
        $st->execute([$status, $id]);
    }

    public static function setClaimed(int $id): void
    {
        $st = self::db()->prepare(
            "UPDATE item SET status = 'CLAIMED', claimedAt = NOW() WHERE itemId = ?"
        );
        $st->execute([$id]);
    }

    public static function setRecipient(int $id, string $name, string $contact, ?string $enrollment): void
    {
        $st = self::db()->prepare(
            'UPDATE item SET recipientName = ?, recipientContactNo = ?, recipientEnrollmentNo = ? WHERE itemId = ?'
        );
        $st->execute([$name, $contact, $enrollment, $id]);
    }

    public static function setResolved(int $id): void
    {
        $st = self::db()->prepare(
            "UPDATE item SET status = 'RESOLVED', resolvedAt = NOW() WHERE itemId = ?"
        );
        $st->execute([$id]);
    }

    public static function updateCustody(int $id, string $location, string $custodyStatus): void
    {
        $st = self::db()->prepare(
            'UPDATE item SET custodyLocation = ?, custodyStatus = ? WHERE itemId = ?'
        );
        $st->execute([$location, $custodyStatus, $id]);
    }

    public static function countForStaff(int $userId, string $itemType, string $status): int
    {
        $st = self::db()->prepare(
            'SELECT COUNT(*) FROM item WHERE itemType = ? AND status = ? AND loggedByStaff = ?'
        );
        $st->execute([$itemType, $status, $userId]);
        return (int) $st->fetchColumn();
    }

    public static function countInCustodyForStaff(int $userId): int
    {
        $st = self::db()->prepare(
            "SELECT COUNT(*) FROM item
             WHERE itemType = 'FOUND'
               AND loggedByStaff = ?
               AND custodyStatus IS NOT NULL
               AND custodyStatus != 'HANDED_OVER'
               AND status IN ('PENDING','ACTIVE','CLAIMED')"
        );
        $st->execute([$userId]);
        return (int) $st->fetchColumn();
    }

    public static function countResolvedForStaff(int $userId, string $sinceTs): int
    {
        $st = self::db()->prepare(
            "SELECT COUNT(*) FROM item
             WHERE itemType = 'FOUND' AND loggedByStaff = ?
               AND status = 'RESOLVED' AND resolvedAt >= ?"
        );
        $st->execute([$userId, $sinceTs]);
        return (int) $st->fetchColumn();
    }

    public static function countResolvedSince(string $sinceTs): int
    {
        $st = self::db()->prepare(
            "SELECT COUNT(*) FROM item WHERE status = 'RESOLVED' AND resolvedAt >= ?"
        );
        $st->execute([$sinceTs]);
        return (int) $st->fetchColumn();
    }

    public static function countByTypeStatus(): array
    {
        $st = self::db()->query(
            'SELECT itemType, status, COUNT(*) AS total FROM item GROUP BY itemType, status'
        );
        return $st->fetchAll();
    }

    public static function countPending(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM item WHERE status = 'PENDING'")->fetchColumn();
    }

    public static function countClaimed(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM item WHERE status = 'CLAIMED'")->fetchColumn();
    }

    public static function countResolved(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM item WHERE status = 'RESOLVED'")->fetchColumn();
    }

    public static function countActive(): int
    {
        return (int) self::db()->query("SELECT COUNT(*) FROM item WHERE status = 'ACTIVE'")->fetchColumn();
    }
}