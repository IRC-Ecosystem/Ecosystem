<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ComplaintService;
use PDO;

final class ComplaintController
{
    private ComplaintService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ComplaintService($pdo);
    }

    public function client(array $session, array $post): array
    {
        $userId = (int) $session['user_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($post['action'] ?? '') === 'submit_complaint') {
            $result = $this->service->submit($userId, $post);
            return array_merge($this->service->clientPage($userId), $result);
        }
        return $this->service->clientPage($userId);
    }

    public function operator(array $query, array $post): array
    {
        $filter = (string) ($query['status'] ?? 'all');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($post['action'] ?? '') === 'resolve_ticket') {
            $result = $this->service->resolve($post);
            return array_merge($this->service->operatorPage($filter), $result);
        }
        return $this->service->operatorPage($filter);
    }
}

