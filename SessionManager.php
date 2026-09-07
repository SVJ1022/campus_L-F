<?php
/**
 * Session manager: secure cookie params, 30-minute inactivity timeout.
 */
declare(strict_types=1);

namespace Findly\Auth;

class SessionManager
{
    /** @var int seconds of inactivity before the session expires */
    private const TIMEOUT = 1800;

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('FINDLY_SESSION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::checkTimeout();
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['userId'] = (int) $user['userId'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['userId']) ? (int) $_SESSION['userId'] : null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private static function checkTimeout(): void
    {
        if (!isset($_SESSION['userId'], $_SESSION['last_activity'])) {
            return;
        }
        if (time() - (int) $_SESSION['last_activity'] > self::TIMEOUT) {
            self::logout();
            session_start();
            return;
        }
        $_SESSION['last_activity'] = time();
    }
}