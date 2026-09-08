<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SubscriptionService;
use PDO;

final class SubscriptionController
{
    private SubscriptionService $service;

    public function __construct(PDO $pdo)
    {
        $this->service = new SubscriptionService($pdo);
    }

    public function client(array $session, array $files): array
    {
        $userId = (int) $session['user_id'];
        $data = $this->service->clientPage($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($files['proof_image'])) {
            $result = $this->service->uploadProof($userId, $files['proof_image']);
            return array_merge($this->service->clientPage($userId), $result);
        }

        return $data;
    }

    public function checkout(array $session, array $post): array
    {
        $userId = (int) $session['user_id'];
        $data = $this->service->clientPage($userId);
        $data['activePage'] = 'langganan';
        $data['pageTitle'] = 'Pembayaran SmartBank';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['pay_smartbank_otp'])) {
            $otpCode = (string) ($post['otp_code'] ?? '');
            $phone = (string) ($post['smartbank_phone'] ?? '');
            return array_merge($data, $this->service->smartbankInstantOtpPayment($userId, $otpCode, $phone));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['pay_smartbank'])) {
            return array_merge($data, $this->service->smartbankCheckout($userId));
        }

        return $data;
    }

    public function admin(array $post): array
    {
        $data = $this->service->adminPage();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($post['action'], $post['payment_id'], $post['user_id'])) {
            $result = $this->service->verifyPayment((int) $post['payment_id'], (int) $post['user_id'], (string) $post['action']);
            return array_merge($this->service->adminPage(), $result);
        }
        return $data;
    }
}

