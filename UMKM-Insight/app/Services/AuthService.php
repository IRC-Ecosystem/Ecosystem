<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use PDO;

final class AuthService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function attempt(string $username, string $password): ?array
    {
        $user = (new UserRepository($this->pdo))->findByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }
        return $user;
    }

    public function registerClient(array $input): array
    {
        $name = sanitize($input['nama'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $business = sanitize($input['bisnis'] ?? '');
        $username = sanitize($input['username'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $confirm = (string) ($input['confirm_password'] ?? '');

        if ($name === '' || $email === '' || $username === '' || $password === '') {
            return ['success' => false, 'message' => 'Semua field wajib diisi.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid.'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password minimal 8 karakter.'];
        }

        if ($password !== $confirm) {
            return ['success' => false, 'message' => 'Konfirmasi password tidak cocok.'];
        }

        $users = new UserRepository($this->pdo);
        if ($users->findByUsername($username)) {
            return ['success' => false, 'message' => 'Username sudah digunakan.'];
        }

        $created = $users->createClient(
            $username,
            password_hash($password, PASSWORD_BCRYPT),
            $name,
            $email,
            $business
        );

        if (!$created) {
            return ['success' => false, 'message' => 'Terjadi kesalahan saat mendaftar.'];
        }

        return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
    }

    public function redirectPathForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.php',
            'operator' => 'operator.php',
            default => 'dashboard.php',
        };
    }
}
