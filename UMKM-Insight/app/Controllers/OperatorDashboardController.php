<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OperatorDashboardService;
use PDO;

final class OperatorDashboardController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function viewData(array $session): array
    {
        return (new OperatorDashboardService($this->pdo))->build((int) ($session['user_id'] ?? 0), $session);
    }
}

