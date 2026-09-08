<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use PDO;

final class AdminDashboardService
{
    private const AVATAR_CLASSES = ['a-green', 'a-blue', 'a-orange', 'a-purple', 'a-teal', 'a-rose'];

    public function __construct(private PDO $pdo)
    {
    }

    public function build(?int $currentUserId, array $session): array
    {
        $users = new UserRepository($this->pdo);
        $currentUser = $currentUserId ? $users->findById($currentUserId) : null;
        $adminName = $currentUser['nama_lengkap'] ?? ($session['nama_lengkap'] ?? 'Admin');

        return [
            'currentUser' => $currentUser,
            'adminName' => $adminName,
            'adminInitials' => uiInitials($adminName),
            'stats' => [
                'clients' => $users->countByRole('client'),
                'operators' => $users->countByRole('operator'),
                'premium' => $users->countPremiumClients(),
                'revenue' => 0,
            ],
            'usersForUi' => $this->mapUsers($users->listForAdmin()),
        ];
    }

    private function mapUsers(array $rows): array
    {
        $result = [];
        foreach ($rows as $idx => $row) {
            $name = $row['nama_lengkap'] ?: $row['username'];
            $isInternal = $row['role'] !== 'client';
            $result[] = [
                'id' => (int) $row['id'],
                'name' => $name,
                'business' => $row['nama_bisnis'] ?: ($isInternal ? 'Akun internal' : '-'),
                'role' => $row['role'],
                'email' => $row['email'],
                'tier' => $isInternal ? 'Internal' : ucfirst($row['tier']),
                'status' => 'Aktif',
                'avatarClass' => self::AVATAR_CLASSES[$idx % count(self::AVATAR_CLASSES)],
                'initials' => uiInitials($name),
            ];
        }
        return $result;
    }

}
