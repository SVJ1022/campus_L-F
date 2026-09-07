<?php
/**
 * FINDLY environment bootstrap.
 * Loads backend/.env (KEY=VALUE, '#' comments) and defines constants.
 */
declare(strict_types=1);

if (!defined('FINDLY_ROOT')) {
    define('FINDLY_ROOT', __DIR__ . '/..');
}

$envFile = FINDLY_ROOT . '/.env';
$vars = [];
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos !== false) {
            $vars[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
        }
    }
}

function findly_env(string $key, $default) {
    global $vars;
    if (isset($vars[$key]) && $vars[$key] !== '') {
        return $vars[$key];
    }
    $g = getenv($key);
    return ($g !== false && $g !== '') ? $g : $default;
}

if (!defined('DB_HOST')) define('DB_HOST', findly_env('DB_HOST', '127.0.0.1'));
if (!defined('DB_PORT')) define('DB_PORT', findly_env('DB_PORT', '3306'));
if (!defined('DB_NAME')) define('DB_NAME', findly_env('DB_NAME', 'findly'));
if (!defined('DB_USER')) define('DB_USER', findly_env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', findly_env('DB_PASS', ''));
if (!defined('CORS_ALLOWED_ORIGIN')) define('CORS_ALLOWED_ORIGIN', findly_env('CORS_ALLOWED_ORIGIN', ''));
if (!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE', (int) findly_env('UPLOAD_MAX_SIZE', 5242880));
