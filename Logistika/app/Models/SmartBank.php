<?php
class SmartBank {
    // Eco SmartBank dialirkan melalui Connector service di compose network
    private static $baseUrl;

    private static function baseUrl(): string {
        if (self::$baseUrl === null) {
            $base = getenv('SMARTBANK_CONNECTOR_URL') ?: 'http://connector:5000';
            self::$baseUrl = rtrim($base, '/') . '/v1/connect';
        }
        return self::$baseUrl;
    }

    private static function authHeaders(): array {
        $key = getenv('SMARTBANK_CONNECTOR_API_KEY') ?: '';
        return ['Content-Type: application/json', 'Authorization: Bearer ' . $key];
    }

    /**
     * Mengambil saldo user dari API SmartBank
     * Connector tidak memiliki endpoint saldo publik; fallback simulasi tetap
     * menjadi jalur utama agar UI LogistiKita tetap berfungsi.
     */
    public static function getBalance($email) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $sessionKey = 'simulated_balance_' . md5($email);
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ($email === 'admin@logistikita.com') ? 5000000.00 : 1000000.00;
        }

        return [
            'status' => 'success',
            'balance' => $_SESSION[$sessionKey],
            'mode' => 'simulated'
        ];
    }

    /**
     * Mengirimkan request pembayaran/pemotongan saldo ke API SmartBank (Connector).
     * Connector memerlukan buyer/seller ter-link + PIN; LogistiKita tidak memiliki
     * alur itu, jadi pemotongan disimulasikan. URL + auth sudah diarahkan ke
     * connector agar jalur integrasi siap dipakai saat model akun SmartBank ditambahkan.
     */
    public static function processTransaction($transaksi_id, $amount, $type = 'payment', $email = 'user@logistikita.com') {
        $url = self::baseUrl() . '/payment-requests';
        $payload = [
            'buyer_external_id' => 'logistikita-' . md5($email),
            'seller_external_id' => 'logistikita-hq',
            'gross_amount' => (string) round($amount, 2),
            'pin' => '',
            'description' => 'Pembayaran Logistik ' . $type . ' (' . $transaksi_id . ')',
            'external_ref_id' => (string) $transaksi_id,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(self::authHeaders(), ['X-Idempotency-Key: lgt-' . $transaksi_id . '-' . md5($type)]));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['success']) && $data['success']) {
                return [
                    'status' => 'success',
                    'bank_ref' => $data['data']['transaction_id'] ?? ('SB-' . time() . '-' . rand(1000, 9999)),
                    'amount' => $amount,
                    'mode' => 'realtime'
                ];
            }
        }

        // Fallback Simulasi jika Connector tidak tersedia / PIN tidak ada
        if (!isset($_SESSION)) {
            session_start();
        }

        $sessionKey = 'simulated_balance_' . md5($email);
        $currentBalance = $_SESSION[$sessionKey] ?? 1000000.00;

        if ($currentBalance >= $amount) {
            $_SESSION[$sessionKey] = $currentBalance - $amount;

            $hqKey = 'simulated_balance_' . md5('admin@logistikita.com');
            $_SESSION[$hqKey] = ($_SESSION[$hqKey] ?? 5000000.00) + $amount;

            return [
                'status' => 'success',
                'bank_ref' => 'SB-SIM-' . time() . '-' . rand(1000, 9999),
                'amount' => $amount,
                'mode' => 'simulated'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Saldo SmartBank tidak mencukupi.',
                'mode' => 'simulated'
            ];
        }
    }

    /**
     * Mengembalikan uang (Refund) ke user jika terjadi pembatalan
     */
    public static function processRefund($transaksi_id, $amount, $email = 'user@logistikita.com') {
        $url = self::baseUrl() . '/payment-requests/refunds';
        $payload = [
            'external_ref_id' => (string) $transaksi_id,
            'reason_code' => 'shipment_cancelled',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(self::authHeaders(), ['X-Idempotency-Key: lgt-ref-' . $transaksi_id]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['success']) && $data['success']) {
                return [
                    'status' => 'success',
                    'bank_ref' => $data['data']['reversal_transaction_id'] ?? ('SB-REF-' . time() . '-' . rand(1000, 9999)),
                    'amount' => $amount,
                    'mode' => 'realtime'
                ];
            }
        }

        // Fallback simulasi refund
        if (!isset($_SESSION)) {
            session_start();
        }

        $sessionKey = 'simulated_balance_' . md5($email);
        $_SESSION[$sessionKey] = ($_SESSION[$sessionKey] ?? 1000000.00) + $amount;

        $hqKey = 'simulated_balance_' . md5('admin@logistikita.com');
        $_SESSION[$hqKey] = ($_SESSION[$hqKey] ?? 5000000.00) - $amount;

        return [
            'status' => 'success',
            'bank_ref' => 'SB-REF-SIM-' . time() . '-' . rand(1000, 9999),
            'amount' => $amount,
            'mode' => 'simulated'
        ];
    }
}
?>
