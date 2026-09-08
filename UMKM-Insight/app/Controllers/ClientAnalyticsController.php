<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ClientAnalyticsService;
use PDO;

final class ClientAnalyticsController
{
    private ClientAnalyticsService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ClientAnalyticsService($pdo);
    }

    public function dashboard(array $session): array
    {
        return $this->service->dashboard((int) $session['user_id']);
    }

    public function salesReport(array $session): array
    {
        return $this->service->salesReport((int) $session['user_id']);
    }

    public function cashflow(array $session): array
    {
        return $this->service->cashflow((int) $session['user_id']);
    }

    public function productPerformance(array $session): array
    {
        return $this->service->productPerformance((int) $session['user_id']);
    }
}

