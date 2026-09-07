<?php
/**
 * bcrypt password wrapper.
 */
declare(strict_types=1);

namespace Findly\Auth;

class PasswordHasher
{
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}