<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OperationsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function tierRequests(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT tr.id, tr.status, tr.requested_at, u.nama_lengkap, u.username, u.nama_bisnis
            FROM tier_requests tr
            JOIN users u ON u.id = tr.user_id
            ORDER BY tr.requested_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function complaints(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.subject, c.message, c.status, c.created_at,
                   COALESCE(u.nama_lengkap, u.username, 'User') AS user_name,
                   COALESCE(u.nama_bisnis, '-') AS business
            FROM complaints c
            JOIN users u ON u.id = c.user_id
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function offers(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, description, price, target_tier
            FROM offers
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

