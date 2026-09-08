<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ComplaintRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $userId, string $subject, string $message): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO complaints (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
        return $stmt->execute([$userId, $subject, $message]);
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function listForOperator(string $status = 'all'): array
    {
        $allowed = ['all', 'open', 'resolved'];
        $status = in_array($status, $allowed, true) ? $status : 'all';

        $sql = 'SELECT c.*, u.nama_lengkap, u.nama_bisnis FROM complaints c JOIN users u ON c.user_id = u.id';
        $params = [];
        if ($status !== 'all') {
            $sql .= ' WHERE c.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY c.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function resolve(int $ticketId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE complaints SET status = 'resolved' WHERE id = ?");
        return $stmt->execute([$ticketId]);
    }

    public function notifyResolved(int $userId, string $subject): bool
    {
        $message = 'Laporan Anda mengenai "' . $subject . '" telah diselesaikan oleh tim operator.';
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'auto', 'Pengaduan Selesai', ?)");
        return $stmt->execute([$userId, $message]);
    }
}

