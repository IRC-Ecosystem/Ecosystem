<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use PDO;
use Throwable;

final class ProfileService
{
    private UserRepository $users;
    private TransactionRepository $transactions;

    public function __construct(private PDO $pdo)
    {
        $this->users = new UserRepository($pdo);
        $this->transactions = new TransactionRepository($pdo);
    }

    public function viewData(int $userId): array
    {
        return [
            'activePage' => 'profile',
            'pageTitle' => 'Profil UMKM',
            'user' => $this->users->findById($userId),
            'userId' => $userId,
            'message' => '',
            'messageType' => '',
        ];
    }

    public function updateProfile(int $userId, array $post, array $files, ?string $currentPhoto): array
    {
        $fotoProfil = $currentPhoto;
        $upload = $this->handleProfileUpload($userId, $files['foto_profil'] ?? null);
        if (!$upload['success']) {
            return $this->result($upload['message'], 'error');
        }
        if ($upload['filename']) {
            $fotoProfil = $upload['filename'];
        }

        $ok = $this->users->updateProfile(
            $userId,
            sanitize($post['nama_bisnis'] ?? ''),
            sanitize($post['kategori'] ?? ''),
            $fotoProfil
        );

        return $ok
            ? $this->result('Profil UMKM berhasil diperbarui.', 'success')
            : $this->result('Gagal memperbarui profil.', 'error');
    }

    public function connectSmartbank(int $userId, array $post): array
    {
        return $this->connectIntegration($userId, 'smartbank_id', sanitize($post['smartbank_id'] ?? ''), 'SmartBank');
    }

    public function disconnectSmartbank(int $userId): array
    {
        return $this->disconnectIntegration($userId, 'smartbank_id', 'SmartBank');
    }

    public function connectWarungpos(int $userId, array $post): array
    {
        return $this->connectIntegration($userId, 'warungpos_id', sanitize($post['warungpos_id'] ?? ''), 'WarungPOS');
    }

    public function disconnectWarungpos(int $userId): array
    {
        return $this->disconnectIntegration($userId, 'warungpos_id', 'WarungPOS');
    }

    private function connectIntegration(int $userId, string $column, string $externalId, string $source): array
    {
        if ($externalId === '') {
            return $this->result("ID {$source} wajib diisi.", 'error');
        }

        $this->pdo->beginTransaction();
        try {
            $this->users->updateIntegrationId($userId, $column, $externalId);
            $this->transactions->clearSourceCache($userId, $source);
            $this->pdo->commit();
            return $this->result("Berhasil menyimpan koneksi {$source}. Data baru akan masuk melalui endpoint integrasi.", 'success');
        } catch (Throwable) {
            $this->pdo->rollBack();
            return $this->result("Gagal menyimpan koneksi {$source}.", 'error');
        }
    }

    private function disconnectIntegration(int $userId, string $column, string $source): array
    {
        $this->pdo->beginTransaction();
        try {
            $this->users->updateIntegrationId($userId, $column, null);
            $this->pdo->commit();
            return $this->result("Koneksi {$source} diputuskan. Data Anda tetap tersimpan sebagai histori.", 'success');
        } catch (Throwable) {
            $this->pdo->rollBack();
            return $this->result("Gagal memutus koneksi {$source}.", 'error');
        }
    }

    private function handleProfileUpload(int $userId, ?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => true, 'filename' => null, 'message' => ''];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['success' => false, 'filename' => null, 'message' => 'Format foto tidak didukung. Hanya JPG, PNG, atau WEBP.'];
        }

        $uploadDir = APP_ROOT . '/assets/uploads/profil';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newName = 'profil_' . $userId . '_' . time() . '.' . $ext;
        $target = $uploadDir . '/' . $newName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['success' => false, 'filename' => null, 'message' => 'Gagal mengunggah foto profil.'];
        }

        return ['success' => true, 'filename' => $newName, 'message' => ''];
    }

    private function result(string $message, string $type): array
    {
        return ['message' => $message, 'messageType' => $type];
    }
}

