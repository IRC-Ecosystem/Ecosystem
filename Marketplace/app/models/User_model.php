<?php

class User_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, role, status, address, phone) VALUES (?, ?, ?, "user", "active", ?, ?)');
        $ok = $stmt->execute([
            trim($data['name']),
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_DEFAULT),
            trim($data['address'] ?? ''),
            trim($data['phone'] ?? ''),
        ]);

        if ($ok) {
            $userId = (int) $this->db->lastInsertId();
            $this->db->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 50000)')->execute([$userId]);
        }

        return $ok;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, COALESCE(w.balance, 0) balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id WHERE u.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function all(): array
    {
        return $this->db->query('SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, COALESCE(w.balance, 0) balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id ORDER BY u.created_at DESC')->fetchAll();
    }

    public function countByRole(): array
    {
        return $this->db->query('SELECT role, COUNT(*) total FROM users GROUP BY role')->fetchAll();
    }

    public function toggleStatus(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET status = IF(status = "active", "suspended", "active") WHERE id = ? AND role != "admin"');
        return $stmt->execute([$id]);
    }
}
