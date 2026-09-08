<?php

class Voucher_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM vouchers WHERE status = "active" ORDER BY min_purchase ASC')->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vouchers WHERE code = ? AND status = "active" LIMIT 1');
        $stmt->execute([strtoupper(trim($code))]);
        return $stmt->fetch() ?: null;
    }

    public function calculateDiscount(?string $code, float $subtotal): array
    {
        if (!$code) {
            return ['code' => null, 'discount' => 0.0, 'error' => null];
        }

        $voucher = $this->findByCode($code);
        if (!$voucher) {
            return ['code' => null, 'discount' => 0.0, 'error' => 'Kode voucher tidak valid.'];
        }

        if ($subtotal < (float) $voucher['min_purchase']) {
            return ['code' => null, 'discount' => 0.0, 'error' => 'Minimal pembelian Rp ' . number_format($voucher['min_purchase'], 0, ',', '.') . ' untuk voucher ini.'];
        }

        $discount = 0.0;
        if ($voucher['discount_type'] === 'percentage') {
            $discount = round(($subtotal * (float) $voucher['discount_value']) / 100);
        } else {
            $discount = (float) $voucher['discount_value'];
        }

        $discount = min($discount, $subtotal);

        return [
            'voucher' => $voucher,
            'code' => $voucher['code'],
            'discount' => $discount,
            'error' => null,
        ];
    }
}
