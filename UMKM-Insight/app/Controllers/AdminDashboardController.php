<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminDashboardService;
use PDO;

final class AdminDashboardController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function viewData(array $session): array
    {
        return (new AdminDashboardService($this->pdo))->build((int) ($session['user_id'] ?? 0), $session);
    }
}

