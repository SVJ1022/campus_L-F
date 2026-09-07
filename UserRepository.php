<?php
/**
 * User data access. Prepared statements only.
 */
declare(strict_types=1);

namespace Findly\User;

use PDO;

class UserRepository
{
    private static function db(): PDO
    {
        return \Database::connect();
    }

    public static function byId(int $id): ?array
    {
        $st = self::db()->prepare(
            'SELECT userId, name, email, role, contactNo, enrollmentNo, accountStatus, createdAt
             FROM user WHERE userId = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function byEmail(string $email): ?array
    {
        $st = self::db()->prepare('SELECT * FROM user WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function create(array $d): int
    {
        $st = self::db()->prepare(
            'INSERT INTO user (name, email, password, role, contactNo, enrollmentNo, accountStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $d['name'],
            $d['email'],
            $d['password'],
            $d['role'] ?? 'STUDENT',
            $d['contactNo'] ?? null,
            $d['enrollmentNo'] ?? null,
            'ACTIVE',
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function all(): array
    {
        $st = self::db()->query(
            'SELECT userId, name, email, role, contactNo, enrollmentNo, accountStatus, createdAt
             FROM user ORDER BY createdAt DESC'
        );
        return $st->fetchAll();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $st = self::db()->prepare('UPDATE user SET accountStatus = ? WHERE userId = ?');
        return $st->execute([$status, $id]);
    }

    public static function countByRole(): array
    {
        $st = self::db()->query(
            "SELECT role, COUNT(*) AS total FROM user
             WHERE accountStatus = 'ACTIVE' GROUP BY role"
        );
        $out = ['STUDENT' => 0, 'STAFF' => 0, 'ADMIN' => 0];
        foreach ($st->fetchAll() as $row) {
            $out[$row['role']] = (int) $row['total'];
        }
        return $out;
    }

    public static function totalUsers(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM user')->fetchColumn();
    }
}