<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use PDO;

final class AuthController
{
    private AuthService $auth;

    public function __construct(PDO $pdo)
    {
        $this->auth = new AuthService($pdo);
    }

    public function redirectPathForRole(string $role): string
    {
        return $this->auth->redirectPathForRole($role);
    }

    public function register(array $input): array
    {
        return $this->auth->registerClient($input);
    }

    public function login(array $input, array &$session): string
    {
        $username = sanitize($input['username'] ?? '');
        $password = (string) ($input['password'] ?? '');

        if ($username === '' || $password === '') {
            return 'Username dan password wajib diisi.';
        }

        $user = $this->auth->attempt($username, $password);
        if (!$user) {
            return 'Username atau password salah.';
        }

        $session['user_id'] = $user['id'];
        $session['username'] = $user['username'];
        $session['role'] = $user['role'];
        $session['nama_lengkap'] = $user['nama_lengkap'];
        $session['tier'] = $user['tier'];

        redirectTo($this->auth->redirectPathForRole($user['role']));
    }
}
