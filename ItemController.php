<?php
/**
 * Shared item endpoints: list, details, create, update, delete.
 * Permission checks for each role happen here,
 * plus recipient-field stripping for non staff/admin responses.
 */
declare(strict_types=1);

namespace Findly\Item;

use Findly\Audit\AuditLogger;
use Findly\Middleware\AuthMiddleware;
use Findly\Notification\NotificationController;
use Findly\Utils\FileUpload;
use Findly\Utils\Request;
use Findly\Utils\Response;
use Findly\Utils\Validator;

class ItemController
{
    // ------------------------------------------------------------------
    // Endpoint actions
    // ------------------------------------------------------------------

    public function index(): void
    {
        $user = AuthMiddleware::user();
        $filters = [
            'type'     => Request::get('type'),
            'status'   => Request::get('status'),
            'category' => Request::get('category'),
            'date'     => Request::get('date'),
            'location' => Request::get('location'),
            'q'        => Request::get('q'),
        ];
        $items = ItemRepository::search($filters, $user);
        self::sanitizeList($items, $user['role']);
        Response::ok(['items' => $items, 'count' => count($items)], '');
    }

    public function show(int $id): void
    {
        $user = AuthMiddleware::user();
        $item = ItemRepository::findById($id);
        if ($item === null) {
            Response::error('Item not found', 404);
        }

        if ($user['role'] === 'STUDENT') {
            $visible = ($item['itemType'] === 'FOUND' && $item['status'] === 'ACTIVE')
                || ($item['itemType'] === 'LOST' && (int) $item['postedBy'] === (int) $user['userId']);
            if (!$visible) {
                Response::forbidden();
            }
        }

        self::sanitizeItem($item, $user['role']);
        Response::ok(['item' => $item], '');
    }

    /** POST /api/items/lost — handled by StudentController::reportLost. */
    public static function createLost(array $user): void
    {
        self::createItem($user, 'LOST');
    }

    /** POST /api/items/found — handled by StaffController::logFound. */
    public static function createFound(array $user): void
    {
        self::createItem($user, 'FOUND');
    }

    /** PUT /api/items/{id} — owner updates own LOST report; Staff/Admin update FOUND items. */
    public function update(int $id): void
    {
        $user = AuthMiddleware::user();
        $item = ItemRepository::findById($id);
        if ($item === null) {
            Response::error('Item not found', 404);
        }

        if ($item['itemType'] === 'LOST') {
            if ((int) $item['postedBy'] !== (int) $user['userId']) {
                Response::forbidden('You can only edit your own reports');
            }
            self::updateItem($user, $item, $id);
            return;
        }

        if ($user['role'] === 'STUDENT') {
            Response::forbidden();
        }
        self::updateItem($user, $item, $id);
    }

    /** DELETE /api/items/{id} — owner only, LOST items only. */
    public function destroy(int $id): void
    {
        $user = AuthMiddleware::user();
        $item = ItemRepository::findById($id);
        if ($item === null) {
            Response::error('Item not found', 404);
        }
        if ($item['itemType'] !== 'LOST') {
            Response::forbidden('Found-item records cannot be deleted');
        }
        if ((int) $item['postedBy'] !== (int) $user['userId']) {
            Response::forbidden('You can only delete your own reports');
        }
        if ($item['status'] !== 'PENDING' && $item['status'] !== 'ACTIVE') {
            Response::error('This report can no longer be deleted', 400);
        }

        ItemRepository::delete($id);
        Response::ok(null, 'Report deleted successfully');
    }

    // ------------------------------------------------------------------
    // Shared create/update internals
    // ------------------------------------------------------------------

    public static function createItem(array $user, string $type): void
    {
        $data = Request::fields();
        $v = new Validator($data);
        $v->required('title')->length('title', 3, 150);
        $v->required('description')->length('description', 10, 5000);
        $v->required('categoryId')->int('categoryId');
        $v->required('location')->length('location', 3, 300);
        $v->required('itemDate')->date('itemDate');
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        try {
            $imageUrl = FileUpload::upload(Request::file('image'));
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }

        $itemData = [
            'title'       => trim((string) $data['title']),
            'description' => trim((string) $data['description']),
            'itemType'    => $type,
            'categoryId'  => (int) $data['categoryId'],
            'location'    => trim((string) $data['location']),
            'itemDate'    => (string) $data['itemDate'],
            'imageUrl'    => $imageUrl,
            'status'      => 'PENDING',
            'postedBy'    => (int) $user['userId'],
        ];

        if ($type === 'FOUND') {
            $itemData['custodyLocation'] = trim((string) ($data['custodyLocation'] ?? $data['location']));
            $itemData['custodyStatus'] = 'IN_CUSTODY';
            $itemData['loggedByStaff'] = (int) $user['userId'];
        }

        $itemId = ItemRepository::create($itemData);
        AuditLogger::log((int) $user['userId'], $itemId, 'POST', null, 'PENDING', ucfirst(strtolower($type)) . ' item created');

        Response::created(['itemId' => $itemId], 'Your ' . strtolower($type) . ' item report has been submitted for review');
    }

    public static function updateItem(array $user, array $item, int $id): void
    {
        $data = Request::fields();
        $v = new Validator($data);
        $v->required('title')->length('title', 3, 150);
        $v->required('description')->length('description', 10, 5000);
        $v->required('categoryId')->int('categoryId');
        $v->required('location')->length('location', 3, 300);
        $v->required('itemDate')->date('itemDate');
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        $fields = [
            'title'      => trim((string) $data['title']),
            'description'=> trim((string) $data['description']),
            'categoryId' => (int) $data['categoryId'],
            'location'   => trim((string) $data['location']),
            'itemDate'   => (string) $data['itemDate'],
        ];

        try {
            $imageUrl = FileUpload::upload(Request::file('image'));
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
        if ($imageUrl !== null) {
            $fields['imageUrl'] = $imageUrl;
        }

        if ($item['itemType'] === 'FOUND' && !empty($data['custodyLocation'])) {
            $fields['custodyLocation'] = trim((string) $data['custodyLocation']);
        }

        ItemRepository::updateFields($id, $fields);
        AuditLogger::log((int) $user['userId'], $id, 'UPDATE', $item['status'], $item['status'], 'Item details updated');

        Response::ok(['itemId' => $id], 'Item updated successfully');
    }

    // ------------------------------------------------------------------
    // Privacy sanitizer
    // ------------------------------------------------------------------

    /**
     * Recipient details are private: never sent to students or unauthenticated users.
     */
    public static function sanitizeItem(array &$item, string $role): void
    {
        if ($role !== 'STAFF' && $role !== 'ADMIN') {
            unset($item['recipientName'], $item['recipientContactNo'], $item['recipientEnrollmentNo']);
        }
    }

    public static function sanitizeList(array &$items, string $role): void
    {
        foreach ($items as &$item) {
            self::sanitizeItem($item, $role);
        }
        unset($item);
    }
}