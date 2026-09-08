<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransactionRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function sumByType(int $userId, string $type): float
    {
        $stmt = $this->pdo->prepare('SELECT SUM(amount) FROM transaction_cache WHERE user_id = ? AND type = ?');
        $stmt->execute([$userId, $type]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public function recent(int $userId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transaction_cache WHERE user_id = ? ORDER BY transaction_date DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function notifications(int $userId, int $limit = 3): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function incomeByDate(int $userId, int $days = 7, ?int $limit = null): array
    {
        $sql = "
            SELECT DATE(transaction_date) as t_date, SUM(amount) as total
            FROM transaction_cache
            WHERE user_id = ? AND type = 'Income'
        ";
        $params = [$userId];

        if ($days > 0) {
            $sql .= " AND transaction_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $days;
        }

        $sql .= ' GROUP BY DATE(transaction_date) ORDER BY t_date ASC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function dailyCashflow(int $userId, int $days = 7): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DATE(transaction_date) as t_date,
                   SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END) as income,
                   SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END) as expense
            FROM transaction_cache
            WHERE user_id = ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(transaction_date)
            ORDER BY t_date ASC
        ");
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }

    public function incomeTransactions(int $userId, ?int $limit = null): array
    {
        $sql = "SELECT * FROM transaction_cache WHERE user_id = ? AND type = 'Income' ORDER BY transaction_date DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function incomeStats(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM transaction_cache WHERE user_id = ? AND type = 'Income'");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: ['count' => 0, 'total' => 0];
    }

    public function clearSourceCache(int $userId, string $source): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM transaction_cache WHERE user_id = ? AND source = ?');
        $stmt->execute([$userId, $source]);
    }
}

