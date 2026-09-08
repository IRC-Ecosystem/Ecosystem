<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use PDO;

final class ClientAnalyticsService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dashboard(int $userId): array
    {
        $user = $this->user($userId);
        $transactions = new TransactionRepository($this->pdo);
        $totalRevenue = $transactions->sumByType($userId, 'Income');
        $totalExpense = $transactions->sumByType($userId, 'Expense');
        [$chartLabels, $chartValues] = $this->mapIncomeChart($transactions->incomeByDate($userId, 7));

        return [
            'user' => $user,
            'userId' => $userId,
            'isPremium' => $user['tier'] === 'premium',
            'pageTitle' => 'Dashboard Analytics',
            'activePage' => 'dashboard',
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalRevenue - $totalExpense,
            'recentTransactions' => $transactions->recent($userId, 5),
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'notifications' => $transactions->notifications($userId, 3),
        ];
    }

    public function salesReport(int $userId): array
    {
        $user = $this->user($userId);
        $isPremium = $user['tier'] === 'premium';
        $transactions = new TransactionRepository($this->pdo);
        $stats = $transactions->incomeStats($userId);
        $totalSalesCount = (int) ($stats['count'] ?: 0);
        $totalRevenue = (float) ($stats['total'] ?: 0);
        [$chartLabels, $chartValues] = $this->mapIncomeChart($transactions->incomeByDate($userId, 0, 30));

        return [
            'user' => $user,
            'userId' => $userId,
            'isPremium' => $isPremium,
            'pageTitle' => 'Laporan Penjualan',
            'activePage' => 'laporan',
            'salesTransactions' => $transactions->incomeTransactions($userId, $isPremium ? null : 10),
            'totalSalesCount' => $totalSalesCount,
            'totalRevenue' => $totalRevenue,
            'avgOrder' => $totalSalesCount > 0 ? $totalRevenue / $totalSalesCount : 0,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
        ];
    }

    public function cashflow(int $userId): array
    {
        $user = $this->user($userId);
        $transactions = new TransactionRepository($this->pdo);
        $totalIn = $transactions->sumByType($userId, 'Income');
        $totalOut = $transactions->sumByType($userId, 'Expense');

        $chartLabels = [];
        $incomeValues = [];
        $expenseValues = [];
        foreach ($transactions->dailyCashflow($userId, 7) as $row) {
            $chartLabels[] = date('d M', strtotime($row['t_date']));
            $incomeValues[] = (float) $row['income'];
            $expenseValues[] = (float) $row['expense'];
        }

        return [
            'user' => $user,
            'userId' => $userId,
            'isPremium' => $user['tier'] === 'premium',
            'pageTitle' => 'Arus Kas (Cashflow)',
            'activePage' => 'arus-kas',
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netCash' => $totalIn - $totalOut,
            'chartLabels' => $chartLabels,
            'incomeValues' => $incomeValues,
            'expenseValues' => $expenseValues,
        ];
    }

    public function productPerformance(int $userId): array
    {
        $user = $this->user($userId);
        $products = new ProductRepository($this->pdo);
        $localProducts = $products->localPerformance($userId);
        $allProducts = $products->registeredProductsWithSales($userId);
        $globalTrends = $products->globalTrends();
        $categoryRows = $products->categoryDistribution($userId);

        $totalLocalSold = array_sum(array_map(fn ($p) => (int) $p['total_sold'], $localProducts));
        $totalLocalRevenue = array_sum(array_map(fn ($p) => (float) $p['total_revenue'], $localProducts));
        $totalGlobalItems = array_sum(array_map(fn ($g) => (int) $g['total_sold_global'], $globalTrends));

        return [
            'user' => $user,
            'userId' => $userId,
            'isPremium' => $user['tier'] === 'premium',
            'pageTitle' => 'Performa Produk',
            'activePage' => 'performa-produk',
            'localProducts' => $localProducts,
            'allProducts' => $allProducts,
            'globalTrends' => $globalTrends,
            'totalLocalSold' => $totalLocalSold,
            'totalLocalRevenue' => $totalLocalRevenue,
            'totalGlobalItems' => $totalGlobalItems,
            'comparisonData' => $this->buildComparison($localProducts, $globalTrends),
            'catLabels' => array_column($categoryRows, 'kategori'),
            'catCounts' => array_map('intval', array_column($categoryRows, 'count')),
        ];
    }

    private function user(int $userId): array
    {
        $user = (new UserRepository($this->pdo))->findById($userId);
        if (!$user) {
            throw new \RuntimeException('User tidak ditemukan.');
        }
        return $user;
    }

    private function mapIncomeChart(array $rows): array
    {
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = date('d M', strtotime($row['t_date']));
            $values[] = (float) $row['total'];
        }
        return [$labels, $values];
    }

    private function buildComparison(array $localProducts, array $globalTrends): array
    {
        $comparisonData = [];
        foreach ($localProducts as $local) {
            $matchedGlobal = null;
            foreach ($globalTrends as $global) {
                if (
                    stripos($local['product_name'], $global['product_name']) !== false ||
                    stripos($global['product_name'], $local['product_name']) !== false
                ) {
                    $matchedGlobal = $global;
                    break;
                }
            }
            $comparisonData[] = [
                'name' => $local['product_name'],
                'local_sold' => (int) $local['total_sold'],
                'local_revenue' => (float) $local['total_revenue'],
                'global_sold' => $matchedGlobal ? (int) $matchedGlobal['total_sold_global'] : 0,
                'global_avg_price' => $matchedGlobal ? (float) $matchedGlobal['avg_price'] : 0,
                'has_match' => $matchedGlobal !== null,
            ];
        }
        return $comparisonData;
    }
}

