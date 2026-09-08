<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OperationsRepository;
use App\Repositories\UserRepository;
use PDO;

final class OperatorDashboardService
{
    private const AVATAR_CLASSES = ['a-green', 'a-blue', 'a-orange', 'a-purple', 'a-teal', 'a-rose'];

    public function __construct(private PDO $pdo)
    {
    }

    public function build(?int $currentUserId, array $session): array
    {
        $users = new UserRepository($this->pdo);
        $ops = new OperationsRepository($this->pdo);
        $currentUser = $currentUserId ? $users->findById($currentUserId) : null;
        $operatorName = $currentUser['nama_lengkap'] ?? ($session['nama_lengkap'] ?? 'Operator');

        $complaints = $this->mapComplaints($ops->complaints());

        return [
            'currentUser' => $currentUser,
            'operatorName' => $operatorName,
            'operatorInitials' => uiInitials($operatorName),
            'pengajuanForUi' => $this->mapTierRequests($ops->tierRequests()),
            'complaintsForUi' => $complaints['complaintsForUi'],
            'ticketsForUi' => $complaints['ticketsForUi'],
            'tierUsersForUi' => $this->mapTierUsers($users->listClients()),
            'promosForUi' => $this->mapOffers($ops->offers()),
        ];
    }

    private function mapTierRequests(array $rows): array
    {
        $result = [];
        foreach ($rows as $idx => $row) {
            $business = $row['nama_bisnis'] ?: ($row['nama_lengkap'] ?: $row['username']);
            $result[] = [
                'id' => (int) $row['id'],
                'business' => $business,
                'time' => date('d/m/y H:i', strtotime($row['requested_at'])),
                'value' => '-',
                'status' => ucfirst($row['status']),
                'avatarClass' => self::AVATAR_CLASSES[$idx % count(self::AVATAR_CLASSES)],
                'initials' => uiInitials($business),
            ];
        }
        return $result;
    }

    private function mapComplaints(array $rows): array
    {
        $complaintsForUi = [];
        $ticketsForUi = [];
        foreach ($rows as $row) {
            $complaintsForUi[] = [
                'subject' => $row['subject'],
                'user' => $row['user_name'],
                'status' => ucfirst($row['status']),
            ];
            $ticketsForUi[] = [
                'id' => 'TK-' . (int) $row['id'],
                'umkm' => $row['business'],
                'user' => $row['user_name'],
                'subject' => $row['subject'],
                'detail' => $row['message'],
                'time' => date('d/m/y H:i', strtotime($row['created_at'])),
                'status' => $row['status'] === 'resolved' ? 'resolved' : 'open',
                'priority' => 'medium',
            ];
        }
        return compact('complaintsForUi', 'ticketsForUi');
    }

    private function mapTierUsers(array $rows): array
    {
        $result = [];
        foreach ($rows as $idx => $row) {
            $name = $row['nama_lengkap'] ?: $row['username'];
            $tier = strtoupper($row['tier']);
            $result[] = [
                'id' => (int) $row['id'],
                'name' => $name,
                'business' => $row['nama_bisnis'] ?: '-',
                'tier' => $tier,
                'lastActivity' => date('d/m/y', strtotime($row['created_at'])),
                'avatarClass' => self::AVATAR_CLASSES[$idx % count(self::AVATAR_CLASSES)],
                'initials' => uiInitials($name),
                'actionLabel' => $tier === 'PREMIUM' ? 'Premium aktif' : 'Free aktif',
                'actionType' => 'readonly',
            ];
        }
        return $result;
    }

    private function mapOffers(array $rows): array
    {
        return array_map(fn (array $row): array => [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'desc' => $row['description'],
            'price' => formatRupiah($row['price']),
            'target' => strtoupper($row['target_tier']),
        ], $rows);
    }
}

