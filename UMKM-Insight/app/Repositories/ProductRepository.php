<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function localPerformance(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.nama_produk as product_name,
                   COUNT(tc.id) as total_sold,
                   SUM(tc.amount) as total_revenue
            FROM transaction_cache tc
            JOIN products p ON tc.product_id = p.id
            WHERE tc.user_id = ? AND tc.source = 'WarungPOS'
            GROUP BY p.nama_produk
            ORDER BY total_sold DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function registeredProductsWithSales(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*,
                   COUNT(tc.id) as total_sold,
                   SUM(tc.amount) as total_revenue
            FROM products p
            LEFT JOIN transaction_cache tc ON p.id = tc.product_id AND tc.type = 'Income'
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function globalTrends(): array
    {
        return $this->pdo
            ->query('SELECT * FROM market_trends_cache ORDER BY total_sold_global DESC')
            ->fetchAll();
    }

    public function categoryDistribution(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT kategori, COUNT(*) as count FROM products WHERE user_id = ? GROUP BY kategori');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

