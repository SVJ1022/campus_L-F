<?php
/**
 * Authentication module: register / login / logout / me.
 */
declare(strict_types=1);

namespace Findly\Auth;

use Findly\Middleware\AuthMiddleware;
use Findly\User\UserRepository;
use Findly\Utils\Request;
use Findly\Utils\Response;
use Findly\Utils\Validator;

class AuthController
{
    public function register(): void
    {
        $data = Request::json();
        $v = new Validator($data);
        $v->required('name')->length('name', 2, 100);
        $v->required('email')->email('email')->length('email', 5, 150);
        $v->required('password')->length('password', 6, 100);
        $v->required('contactNo')->phone('contactNo');
        if (!isset($data['confirmPassword']) || $data['confirmPassword'] !== ($data['password'] ?? null)) {
            $v->add('confirmPassword', 'Password confirmation does not match');
        }
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        $email = strtolower(trim((string) $data['email']));
        if (UserRepository::byEmail($email) !== null) {
            Response::error('An account with this email already exists', 409);
        }

        $userId = UserRepository::create([
            'name'       => trim((string) $data['name']),
            'email'      => $email,
            'password'   => PasswordHasher::hash((string) $data['password']),
            'role'       => 'STUDENT',
            'contactNo'  => trim((string) $data['contactNo']),
            'enrollmentNo' => null,
        ]);

        $user = UserRepository::byId($userId);
        SessionManager::login($user);
        Response::created($user, 'Registration successful. Welcome to FINDLY!');
    }

    public function login(): void
    {
        $data = Request::json();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::error('Email and password are required', 422);
        }

        $user = UserRepository::byEmail($email);
        if ($user === null || !PasswordHasher::verify($password, $user['password'])) {
            Response::error('Invalid email or password', 401);
        }
        if ($user['accountStatus'] !== 'ACTIVE') {
            $message = $user['accountStatus'] === 'SUSPENDED'
                ? 'Your account has been suspended. Contact the administrator.'
                : 'Your account is not active. Contact the administrator.';
            Response::error($message, 403);
        }

        unset($user['password']);
        SessionManager::login($user);
        Response::ok($user, 'Login successful');
    }

    public function logout(): void
    {
        SessionManager::init();
        SessionManager::logout();
        Response::ok(null, 'Logged out successfully');
    }

    public function me(): void
    {
        $user = AuthMiddleware::user();
        Response::ok($user, '');
    }

    public function updateProfile(): void
    {
        $user = AuthMiddleware::user();
        $data = Request::json();
        $v = new Validator($data);
        $v->required('name')->length('name', 2, 100);
        $v->required('contactNo')->phone('contactNo');
        $v->required('enrollmentNo')->length('enrollmentNo', 3, 30);
        if (!empty($data['password'])) {
            $v->length('password', 6, 100);
        }
        if ($v->fails()) {
            Response::error('Validation failed', 422, ['errors' => $v->errors()]);
        }

        $db = \Database::connect();
        $params = [
            trim((string) $data['name']),
            trim((string) $data['contactNo']),
            trim((string) $data['enrollmentNo']),
        ];
        $sql = 'UPDATE user SET name = ?, contactNo = ?, enrollmentNo = ?';

        $newPassword = null;
        if (!empty($data['password'])) {
            $sql .= ', password = ?';
            $newPassword = PasswordHasher::hash((string) $data['password']);
        }
        $sql .= ' WHERE userId = ?';
        if ($newPassword !== null) {
            $params[] = $newPassword;
        }
        $params[] = (int) $user['userId'];

        $db->prepare($sql)->execute($params);
        Response::ok(UserRepository::byId((int) $user['userId']), 'Profile updated');
    }
}