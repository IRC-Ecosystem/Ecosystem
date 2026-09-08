<?php

class SmartBank_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->db->exec('CREATE TABLE IF NOT EXISTS smartbank_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL UNIQUE,
            payment_request_id VARCHAR(191) NULL,
            transaction_id VARCHAR(191) NULL,
            status ENUM("pending", "success", "failed") NOT NULL DEFAULT "pending",
            response_body JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_smartbank_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function request(string $path, string $method = 'GET', ?array $body = null, ?string $idempotencyKey = null): array
    {
        if (SMARTBANK_CONNECTOR_API_KEY === '') {
            throw new RuntimeException('API key SmartBank Connector belum dikonfigurasi.');
        }

        $curl = curl_init(SMARTBANK_CONNECTOR_URL . $path);
        $headers = [
            'Authorization: Bearer ' . SMARTBANK_CONNECTOR_API_KEY,
            'Content-Type: application/json',
            'X-Request-Id: marketplace-' . bin2hex(random_bytes(8)),
        ];
        if ($idempotencyKey !== null) {
            $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
        }
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => SMARTBANK_CONNECTOR_TIMEOUT_MS,
            CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR),
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($raw === false || $error !== '') {
            throw new RuntimeException('SmartBank Connector tidak dapat dijangkau.');
        }
        $payload = json_decode($raw, true);
        if ($status < 200 || $status >= 300 || ($payload['success'] ?? true) === false) {
            throw new RuntimeException($payload['error']['message'] ?? 'Pembayaran SmartBank gagal.');
        }
        return $payload['data'] ?? $payload;
    }

    public function buyerExternalId(int $userId): string
    {
        return 'marketplace-user-' . $userId;
    }

    public function requestOtp(string $phone, string $scope): array
    {
        return $this->request('/v1/connect/users/otp/request', 'POST', ['phone' => $phone, 'purpose' => 'WALLET_LINK'], 'marketplace-otp-' . $scope . '-' . bin2hex(random_bytes(8)));
    }

    public function verifyOtp(string $requestId, string $code, string $scope): array
    {
        return $this->request('/v1/connect/users/otp/verify', 'POST', ['request_id' => $requestId, 'code' => $code], 'marketplace-verify-' . $scope . '-' . $requestId);
    }

    public function link(string $externalId, string $verificationToken): array
    {
        return $this->request('/v1/connect/users/link', 'POST', ['external_user_id' => $externalId, 'verification_token' => $verificationToken], 'marketplace-link-' . $externalId);
    }

    public function linkage(string $externalId): array
    {
        return $this->request('/v1/connect/users/' . rawurlencode($externalId));
    }

    public function pay(array $order, int $buyerId, string $pin): array
    {
        return $this->request('/v1/connect/payment-requests', 'POST', [
            'buyer_external_id' => $this->buyerExternalId($buyerId),
            'seller_external_id' => $order['seller_external_id'] ?: SMARTBANK_MARKETPLACE_EXTERNAL_ID,
            'gross_amount' => (string) round((float) $order['total']),
            'pin' => $pin,
            'description' => 'Pembayaran PasarKita ' . $order['order_code'],
            'external_ref_id' => $order['order_code'],
        ], 'marketplace-' . $order['order_code']);
    }

    public function recordPayment(int $orderId, array $result): void
    {
        $stmt = $this->db->prepare('INSERT INTO smartbank_payments (order_id, payment_request_id, transaction_id, status, response_body) VALUES (?, ?, ?, "success", ?) ON DUPLICATE KEY UPDATE payment_request_id = VALUES(payment_request_id), transaction_id = VALUES(transaction_id), status = "success", response_body = VALUES(response_body)');
        $stmt->execute([$orderId, $result['payment_request_id'] ?? null, $result['transaction_id'] ?? null, json_encode($result)]);
    }
}
