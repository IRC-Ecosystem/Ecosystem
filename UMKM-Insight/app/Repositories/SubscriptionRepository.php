<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SubscriptionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createPayment(int $userId, string $proofImage, string $status = 'pending'): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO subscription_payments (user_id, proof_image, status) VALUES (?, ?, ?)');
        return $stmt->execute([$userId, $proofImage, $status]);
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM subscription_payments WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function listAllWithUsers(): array
    {
        return $this->pdo->query("
            SELECT p.*, u.nama_lengkap, u.nama_bisnis, u.smartbank_id
            FROM subscription_payments p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ")->fetchAll();
    }

    public function updateStatus(int $paymentId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE subscription_payments SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $paymentId]);
    }
}

