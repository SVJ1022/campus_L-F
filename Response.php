<?php
/**
 * Standard JSON envelope helper.
 * Every API response uses: { success, data, message }
 */
declare(strict_types=1);

namespace Findly\Utils;

class Response
{
    public static function json($data = null, int $status = 200, string $message = ''): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $status < 400,
            'data'    => $data,
            'message' => $message,
        ]);
        exit;
    }

    public static function ok($data = null, string $message = ''): void
    {
        self::json($data, 200, $message);
    }

    public static function created($data = null, string $message = ''): void
    {
        self::json($data, 201, $message);
    }

    public static function error(string $message, int $status = 400, $data = null): void
    {
        self::json($data, $status, $message);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json(null, 401, $message);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::json(null, 403, $message);
    }
}
