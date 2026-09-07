<?php
/**
 * FINDLY — single front controller.
 * Every request enters here and is routed via routes/api.php.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require __DIR__ . '/../config/env.php';
require __DIR__ . '/../config/db.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'Findly\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use Findly\Utils\Response;
use Findly\Auth\SessionManager;

// ---- CORS for local development (frontend served on a different port) ----
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && defined('CORS_ALLOWED_ORIGIN') && CORS_ALLOWED_ORIGIN !== '') {
    $allowed = array_map('trim', explode(',', CORS_ALLOWED_ORIGIN));
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    }
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Session (secure cookie params) ----
SessionManager::init();

// ---- Route resolution ----
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Strip the base path (e.g. /findly/backend/public) from the URI.
$base = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
if ($base !== '/' && $base !== '' && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . trim($uri, '/');

$router = require __DIR__ . '/../routes/api.php';
if (!$router->dispatch($method, $uri)) {
    Response::json(null, 404, 'Route not found');
}
