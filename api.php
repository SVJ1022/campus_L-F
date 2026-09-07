<?php
/**
 * FINDLY route map.
 * Maps method + path to a controller action. No business logic lives here.
 */
declare(strict_types=1);

use Findly\Admin\AdminController;
use Findly\Auth\AuthController;
use Findly\Category\CategoryController;
use Findly\Item\ItemController;
use Findly\Notification\NotificationController;
use Findly\Staff\StaffController;
use Findly\Student\StudentController;

return new class {
    private array $routes = [
        // Auth
        'POST   /api/auth/register'          => [AuthController::class, 'register'],
        'POST   /api/auth/login'             => [AuthController::class, 'login'],
        'POST   /api/auth/logout'            => [AuthController::class, 'logout'],
        'GET    /api/auth/me'                => [AuthController::class, 'me'],
        'GET    /api/profile'                => [AuthController::class, 'me'],
        'PUT    /api/profile'                => [AuthController::class, 'updateProfile'],

        // Items (shared, permission-checked inside controllers)
        'GET    /api/items'                  => [ItemController::class, 'index'],
        'GET    /api/items/{id}'             => [ItemController::class, 'show'],
        'POST   /api/items/lost'             => [StudentController::class, 'reportLost'],
        'POST   /api/items/found'            => [StaffController::class, 'logFound'],
        'PUT    /api/items/{id}'             => [ItemController::class, 'update'],
        'DELETE /api/items/{id}'             => [ItemController::class, 'destroy'],
        'PUT    /api/items/{id}/custody'     => [StaffController::class, 'custody'],
        'PUT    /api/items/{id}/claim'       => [StaffController::class, 'claim'],
        'PUT    /api/items/{id}/recipient'   => [StaffController::class, 'recipient'],
        'PUT    /api/items/{id}/resolve'     => [StaffController::class, 'resolve'],
        'PUT    /api/items/{id}/moderate'    => [AdminController::class, 'moderate'],

        // Categories
        'GET    /api/categories'             => [CategoryController::class, 'index'],
        'POST   /api/categories'             => [CategoryController::class, 'store'],
        'PUT    /api/categories/{id}'        => [CategoryController::class, 'update'],
        'DELETE /api/categories/{id}'        => [CategoryController::class, 'destroy'],

        // Users
        'GET    /api/users'                  => [AdminController::class, 'users'],
        'PUT    /api/users/{id}/status'      => [AdminController::class, 'setUserStatus'],

        // Notifications
        'GET    /api/notifications'          => [NotificationController::class, 'index'],
        'PUT    /api/notifications/{id}/read'=> [NotificationController::class, 'markRead'],

        // Dashboards
        'GET    /api/dashboard/student'      => [StudentController::class, 'summary'],
        'GET    /api/dashboard/staff'        => [StaffController::class, 'dashboard'],
        'GET    /api/dashboard/admin'        => [AdminController::class, 'dashboard'],
    ];

    public function dispatch(string $method, string $uri): bool
    {
        foreach ($this->routes as $route => $handler) {
            [$routeMethod, $pattern] = preg_split('/\s+/', trim($route), 2);
            if ($routeMethod !== $method) {
                continue;
            }
            $regex = '#^' . preg_replace('#\{id\}#', '(\d+)', $pattern) . '$#';
            if (!preg_match($regex, $uri, $matches)) {
                continue;
            }
            array_shift($matches);
            $matches = array_map('intval', $matches);
            [$class, $action] = $handler;
            (new $class())->{$action}(...$matches);
            return true;
        }
        return false;
    }
};