<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createClient(string $username, string $passwordHash, string $name, string $email, string $businessName): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, nama_lengkap, email, nama_bisnis, role, tier)
            VALUES (?, ?, ?, ?, ?, 'client', 'free')
        ");
        return $stmt->execute([$username, $passwordHash, $name, $email, $businessName]);
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }

    public function countPremiumClients(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND tier = 'premium'")
            ->fetchColumn();
    }

    public function listForAdmin(): array
    {
        return $this->pdo->query("
            SELECT id, nama_lengkap, username, email, role, nama_bisnis, tier
            FROM users
            ORDER BY FIELD(role, 'admin', 'operator', 'client'), id
        ")->fetchAll();
    }

    public function listClients(): array
    {
        return $this->pdo->query("
            SELECT id, nama_lengkap, username, nama_bisnis, tier, created_at
            FROM users
            WHERE role = 'client'
            ORDER BY id DESC
        ")->fetchAll();
    }

    public function updateProfile(int $userId, string $namaBisnis, string $kategori, ?string $fotoProfil): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET nama_bisnis = ?, kategori = ?, foto_profil = ? WHERE id = ?');
        return $stmt->execute([$namaBisnis, $kategori, $fotoProfil, $userId]);
    }

    public function updateIntegrationId(int $userId, string $column, ?string $value): bool
    {
        $allowed = ['smartbank_id', 'warungpos_id'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Kolom integrasi tidak valid.');
        }
        $stmt = $this->pdo->prepare("UPDATE users SET {$column} = ? WHERE id = ?");
        return $stmt->execute([$value, $userId]);
    }
}
