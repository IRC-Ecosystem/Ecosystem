<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProfileService;
use PDO;

final class ProfileController
{
    private ProfileService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new ProfileService($pdo);
    }

    public function handle(array $session, array $post, array $files): array
    {
        $userId = (int) $session['user_id'];
        $data = $this->service->viewData($userId);

        if (($post['update_profile'] ?? null) !== null) {
            $result = $this->service->updateProfile($userId, $post, $files, $data['user']['foto_profil'] ?? null);
            return $this->refresh($userId, $result);
        }

        if (($post['connect_smartbank'] ?? null) !== null) {
            return $this->refresh($userId, $this->service->connectSmartbank($userId, $post));
        }

        if (($post['disconnect_smartbank'] ?? null) !== null) {
            return $this->refresh($userId, $this->service->disconnectSmartbank($userId));
        }

        if (($post['connect_warungpos'] ?? null) !== null) {
            return $this->refresh($userId, $this->service->connectWarungpos($userId, $post));
        }

        if (($post['disconnect_warungpos'] ?? null) !== null) {
            return $this->refresh($userId, $this->service->disconnectWarungpos($userId));
        }

        return $data;
    }

    private function refresh(int $userId, array $result): array
    {
        return array_merge($this->service->viewData($userId), $result);
    }
}

