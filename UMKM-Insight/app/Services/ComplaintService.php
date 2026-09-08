<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ComplaintRepository;
use App\Repositories\UserRepository;
use PDO;

final class ComplaintService
{
    private ComplaintRepository $complaints;
    private UserRepository $users;

    public function __construct(private PDO $pdo)
    {
        $this->complaints = new ComplaintRepository($pdo);
        $this->users = new UserRepository($pdo);
    }

    public function clientPage(int $userId): array
    {
        return [
            'user' => $this->users->findById($userId),
            'pageTitle' => 'Pusat Bantuan',
            'activePage' => 'pengaduan',
            'success' => '',
            'error' => '',
            'complaints' => $this->complaints->listByUser($userId),
        ];
    }

    public function submit(int $userId, array $post): array
    {
        $subject = sanitize($post['subject'] ?? '');
        $message = sanitize($post['message'] ?? '');
        if ($subject === '' || $message === '') {
            return ['success' => '', 'error' => 'Subjek dan pesan wajib diisi.'];
        }
        return $this->complaints->create($userId, $subject, $message)
            ? ['success' => 'Pengaduan Anda telah dikirim ke tim operator.', 'error' => '']
            : ['success' => '', 'error' => 'Terjadi kesalahan saat mengirim pengaduan.'];
    }

    public function operatorPage(string $status): array
    {
        $filter = in_array($status, ['all', 'open', 'resolved'], true) ? $status : 'all';
        return [
            'user' => null,
            'pageTitle' => 'Manajemen Tiket Bantuan',
            'activePage' => 'pengaduan-admin',
            'success' => '',
            'error' => '',
            'filter' => $filter,
            'complaints' => $this->complaints->listForOperator($filter),
        ];
    }

    public function resolve(array $post): array
    {
        $ticketId = (int) ($post['ticket_id'] ?? 0);
        $userId = (int) ($post['target_user_id'] ?? 0);
        $subject = sanitize($post['ticket_subject'] ?? '');
        if ($ticketId <= 0 || $userId <= 0) {
            return ['success' => '', 'error' => 'Data tiket tidak valid.'];
        }

        if ($this->complaints->resolve($ticketId)) {
            $this->complaints->notifyResolved($userId, $subject);
            return ['success' => 'Tiket berhasil diselesaikan dan pengguna telah dinotifikasi.', 'error' => ''];
        }
        return ['success' => '', 'error' => 'Gagal menyelesaikan tiket.'];
    }
}

