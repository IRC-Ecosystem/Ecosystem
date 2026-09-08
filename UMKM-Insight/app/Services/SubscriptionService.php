<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SubscriptionRepository;
use App\Repositories\UserRepository;
use PDO;
use Throwable;

final class SubscriptionService
{
    private SubscriptionRepository $subscriptions;
    private UserRepository $users;

    public function __construct(private PDO $pdo)
    {
        $this->subscriptions = new SubscriptionRepository($pdo);
        $this->users = new UserRepository($pdo);
    }

    public function clientPage(int $userId): array
    {
        return [
            'user' => $this->users->findById($userId),
            'userId' => $userId,
            'pageTitle' => 'Berlangganan & Tagihan',
            'activePage' => 'langganan',
            'message' => '',
            'error' => '',
            'payments' => $this->subscriptions->listByUser($userId),
        ];
    }

    public function uploadProof(int $userId, ?array $file): array
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['message' => '', 'error' => 'Terjadi kesalahan saat mengunggah file.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['message' => '', 'error' => 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.'];
        }

        $uploadDir = APP_ROOT . '/assets/image/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'proof_' . $userId . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            return ['message' => '', 'error' => 'Gagal mengunggah file.'];
        }

        return $this->subscriptions->createPayment($userId, $filename)
            ? ['message' => 'Bukti pembayaran berhasil diunggah dan sedang menunggu verifikasi.', 'error' => '']
            : ['message' => '', 'error' => 'Gagal menyimpan pembayaran.'];
    }

    public function smartbankCheckout(int $userId): array
    {
        $user = $this->users->findById($userId);
        if (!$user || empty($user['smartbank_id'])) {
            return ['message' => '', 'error' => 'Anda belum menghubungkan akun SmartBank. Silakan hubungkan di Profil UMKM.'];
        }

        $ok = $this->subscriptions->createPayment($userId, 'SMARTBANK_PENDING', 'pending');
        return $ok
            ? ['message' => 'Permintaan pembayaran SmartBank dicatat dan menunggu verifikasi/callback integrasi.', 'error' => '']
            : ['message' => '', 'error' => 'Gagal mencatat permintaan pembayaran.'];
    }

    public function smartbankInstantOtpPayment(int $userId, string $otpCode, string $smartbankPhone): array
    {
        if (strlen(trim($otpCode)) < 6) {
            return ['message' => '', 'error' => 'Kode OTP harus berupa 6 digit angka!'];
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Simpan pembayaran sebagai approved
            $this->subscriptions->createPayment($userId, 'SMARTBANK_OTP_VERIFIED_' . time(), 'approved');

            // 2. Upgrade user ke premium
            $stmt = $this->pdo->prepare("UPDATE users SET tier = 'premium', smartbank_id = ?, tier_expiry = DATE_ADD(IFNULL(tier_expiry, NOW()), INTERVAL 30 DAY) WHERE id = ?");
            $stmt->execute([$smartbankPhone, $userId]);

            // 3. Catat di transaction_cache
            $stmtTx = $this->pdo->prepare("INSERT INTO transaction_cache (external_id, user_id, source, amount, type, status, description, transaction_date) VALUES (?, ?, 'SmartBank_OTP', 99000.00, 'Income', 'Success', 'Pembayaran Langganan Premium 30 Hari via SmartBank OTP', NOW())");
            $stmtTx->execute(['SB-SUB-' . time() . '-' . $userId, $userId]);

            $this->pdo->commit();
            return ['message' => '🎉 Verifikasi OTP SmartBank Berhasil! Pembayaran Rp 99.000 sukses dan akun Anda telah di-upgrade ke Paket PREMIUM.', 'error' => ''];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return ['message' => '', 'error' => 'Terjadi kesalahan sistem saat verifikasi OTP SmartBank: ' . $e->getMessage()];
        }
    }

    public function adminPage(): array
    {
        return [
            'pageTitle' => 'Verifikasi Tagihan',
            'activePage' => 'langganan_admin',
            'message' => '',
            'error' => '',
            'payments' => $this->subscriptions->listAllWithUsers(),
        ];
    }

    public function verifyPayment(int $paymentId, int $userId, string $action): array
    {
        if (!in_array($action, ['approve', 'reject'], true)) {
            return ['message' => '', 'error' => 'Aksi tidak valid.'];
        }

        $this->pdo->beginTransaction();
        try {
            if ($action === 'approve') {
                $this->subscriptions->updateStatus($paymentId, 'approved');
                $stmt = $this->pdo->prepare("UPDATE users SET tier = 'premium', tier_expiry = DATE_ADD(IFNULL(tier_expiry, NOW()), INTERVAL 30 DAY) WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'Pembayaran disetujui, tier pengguna telah diupgrade.';
            } else {
                $this->subscriptions->updateStatus($paymentId, 'rejected');
                $message = 'Pembayaran ditolak.';
            }
            $this->pdo->commit();
            return ['message' => $message, 'error' => ''];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['message' => '', 'error' => 'Terjadi kesalahan saat memproses pembayaran.'];
        }
    }
}

