<?php
/**
 * HTTP request helpers.
 */
declare(strict_types=1);

namespace Findly\Utils;

class Request
{
    private static ?array $multipartCache = null;

    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function all(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            return self::json();
        }
        if (stripos($contentType, 'multipart/form-data') !== false) {
            return self::fields();
        }
        return array_merge($_GET, $_POST);
    }

    /**
     * Form fields for any content type / HTTP method.
     * PUT multipart bodies are parsed manually because PHP only populates
     * $_POST/$_FILES for POST requests.
     */
    public static function fields(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (stripos($contentType, 'multipart/form-data') !== false) {
            if ($method === 'POST') {
                return $_POST;
            }
            return self::parseMultipart()['fields'];
        }
        if (stripos($contentType, 'application/json') !== false) {
            return self::json();
        }
        return array_merge($_GET, $_POST);
    }

    /**
     * Uploaded file entry (shaped like a $_FILES element) or null.
     */
    public static function file(string $key = 'image'): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (stripos($contentType, 'multipart/form-data') !== false && $method === 'PUT') {
            return self::parseMultipart()['files'][$key] ?? null;
        }
        return $_FILES[$key] ?? null;
    }

    private static function parseMultipart(): array
    {
        if (self::$multipartCache !== null) {
            return self::$multipartCache;
        }
        $result = ['fields' => [], 'files' => []];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!preg_match('/boundary=(.+)$/i', $contentType, $m)) {
            return $result;
        }
        $boundary = trim($m[1], '"');
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $result;
        }

        foreach (explode('--' . $boundary, $raw) as $part) {
            $part = ltrim($part, "\r\n");
            if ($part === '' || $part === '--' || $part === "--\r\n") {
                continue;
            }
            $headerEnd = strpos($part, "\r\n\r\n");
            if ($headerEnd === false) {
                continue;
            }
            $headers = substr($part, 0, $headerEnd);
            $content = substr($part, $headerEnd + 4);
            // Trailing CRLF before the next --boundary belongs to the framing.
            $content = rtrim($content, "\r\n");

            $hasName = preg_match('/name="([^"]+)"/', $headers, $nameMatch);
            $hasFile = preg_match('/filename="([^"]*)"/', $headers, $fileMatch);
            if (!$hasName) {
                continue;
            }
            if ($hasFile && $fileMatch[1] !== '') {
                $tmp = tempnam(sys_get_temp_dir(), 'finupload');
                file_put_contents($tmp, $content);
                $mime = preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $ct) ? trim($ct[1]) : 'application/octet-stream';
                $result['files'][$nameMatch[1]] = [
                    'name'     => $fileMatch[1],
                    'type'     => $mime,
                    'tmp_name' => $tmp,
                    'error'    => UPLOAD_ERR_OK,
                    'size'     => strlen($content),
                ];
            } else {
                $result['fields'][$nameMatch[1]] = $content;
            }
        }

        self::$multipartCache = $result;
        return $result;
    }

    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
}
