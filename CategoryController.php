<?php
/**
 * Category module: read for all, CRUD for Admin only.
 */
declare(strict_types=1);

namespace Findly\Category;

use PDO;
use Findly\Audit\AuditLogger;
use Findly\Middleware\AuthMiddleware;
use Findly\Utils\Request;
use Findly\Utils\Response;
use Findly\Utils\Validator;

class CategoryController
{
    public function index(): void
    {
        AuthMiddleware::user();
        $rows = \Database::connect()->query(
            'SELECT categoryId, categoryName FROM category ORDER BY categoryName'
        )->fetchAll();
        Response::ok(['categories' => $rows], '');
    }

    public function store(): void
    {
        $user = AuthMiddleware::requireRole('ADMIN');
        $data = Request::json();
        $v = new Validator($data);
        $v->required('categoryName')->length('categoryName', 2, 80);
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        $name = trim((string) $data['categoryName']);
        $db = \Database::connect();
        if (self::nameExists($db, $name)) {
            Response::error('A category with this name already exists', 409);
        }

        $st = $db->prepare('INSERT INTO category (categoryName) VALUES (?)');
        $st->execute([$name]);
        $id = (int) $db->lastInsertId();
        AuditLogger::log((int) $user['userId'], null, 'CATEGORY_CHANGE', null, null, 'Category created: ' . $name);
        Response::created(['categoryId' => $id], 'Category added');
    }

    public function update(int $id): void
    {
        $user = AuthMiddleware::requireRole('ADMIN');
        $data = Request::json();
        $v = new Validator($data);
        $v->required('categoryName')->length('categoryName', 2, 80);
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        $name = trim((string) $data['categoryName']);
        $db = \Database::connect();
        $st = $db->prepare('SELECT categoryId FROM category WHERE categoryId = ?');
        $st->execute([$id]);
        if (!$st->fetch()) {
            Response::error('Category not found', 404);
        }
        if (self::nameExists($db, $name, $id)) {
            Response::error('A category with this name already exists', 409);
        }

        $db->prepare('UPDATE category SET categoryName = ? WHERE categoryId = ?')->execute([$name, $id]);
        AuditLogger::log((int) $user['userId'], null, 'CATEGORY_CHANGE', null, null, 'Category renamed to: ' . $name);
        Response::ok(['categoryId' => $id], 'Category updated');
    }

    public function destroy(int $id): void
    {
        $user = AuthMiddleware::requireRole('ADMIN');
        $db = \Database::connect();
        $st = $db->prepare('SELECT categoryName FROM category WHERE categoryId = ?');
        $st->execute([$id]);
        $name = $st->fetchColumn();
        if ($name === false) {
            Response::error('Category not found', 404);
        }

        $st = $db->prepare('SELECT COUNT(*) FROM item WHERE categoryId = ?');
        $st->execute([$id]);
        if ((int) $st->fetchColumn() > 0) {
            Response::error('This category is used by one or more items and cannot be deleted', 400);
        }

        $db->prepare('DELETE FROM category WHERE categoryId = ?')->execute([$id]);
        AuditLogger::log((int) $user['userId'], null, 'CATEGORY_CHANGE', null, null, 'Category deleted: ' . $name);
        Response::ok(null, 'Category deleted');
    }

    private static function nameExists(PDO $db, string $name, int $excludeId = 0): bool
    {
        $st = $db->prepare('SELECT categoryId FROM category WHERE categoryName = ? AND categoryId != ?');
        $st->execute([$name, $excludeId]);
        return $st->fetch() !== false;
    }
}