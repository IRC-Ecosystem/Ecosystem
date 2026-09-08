<?php

class Cart_model
{
    public function items(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            return [];
        }

        $productModel = new Product_model();
        $items = [];
        foreach ($cart as $productId => $qty) {
            $product = $productModel->find((int) $productId);
            if ($product && $product['stock'] > 0) {
                $qty = min((int) $qty, (int) $product['stock']);
                $items[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'subtotal' => $qty * (float) $product['price'],
                ];
            }
        }

        return $items;
    }

    public function add(int $productId, int $qty): void
    {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + max(1, $qty);
    }

    public function remove(int $productId): void
    {
        unset($_SESSION['cart'][$productId]);
    }

    public function clear(): void
    {
        unset($_SESSION['cart']);
        unset($_SESSION['voucher_code']);
    }

    public function setVoucher(?string $code): void
    {
        if ($code) {
            $_SESSION['voucher_code'] = strtoupper(trim($code));
        } else {
            unset($_SESSION['voucher_code']);
        }
    }

    public function getVoucher(): ?string
    {
        return $_SESSION['voucher_code'] ?? null;
    }

    public function summary(): array
    {
        $items = $this->items();
        $subtotal = array_sum(array_column($items, 'subtotal'));

        $voucherCode = $this->getVoucher();
        $discount = 0.0;
        $voucherError = null;

        if ($voucherCode) {
            $voucherModel = new Voucher_model();
            $result = $voucherModel->calculateDiscount($voucherCode, $subtotal);
            $discount = $result['discount'];
            $voucherError = $result['error'];
            if ($voucherError) {
                $this->setVoucher(null);
            }
        }

        $discountedSubtotal = max(0, $subtotal - $discount);
        $marketplaceFee = round($discountedSubtotal * 0.02);
        $gatewayFee = round($discountedSubtotal * 0.005);
        $bankFee = round($discountedSubtotal * 0.01);
        $tax = round($discountedSubtotal * 0.02);
        $shipping = $subtotal > 0 ? max(5000, round($subtotal * 0.05)) : 0;

        $storeItems = [];
        foreach ($items as $item) {
            $storeItems[$item['product']['store_id']][] = $item;
        }
        $storeSummaries = array_map(fn (array $group) => $this->summaryForItems($group), $storeItems);
        $total = $discountedSubtotal + $marketplaceFee + $gatewayFee + $bankFee + $tax + $shipping;

        return compact('items', 'subtotal', 'voucherCode', 'discount', 'voucherError', 'marketplaceFee', 'gatewayFee', 'bankFee', 'tax', 'shipping', 'storeSummaries', 'total');
    }
    
    private function summaryForItems(array $items): array
    {
        $subtotal = array_sum(array_column($items, 'subtotal'));
        $marketplaceFee = round($subtotal * 0.02);
        $gatewayFee = round($subtotal * 0.005);
        $bankFee = round($subtotal * 0.01);
        $tax = round($subtotal * 0.02);
        $shipping = max(5000, round($subtotal * 0.05));
        $product = $items[0]['product'];

        return compact('items', 'subtotal', 'marketplaceFee', 'gatewayFee', 'bankFee', 'tax', 'shipping') + [
            'total' => $subtotal + $marketplaceFee + $gatewayFee + $bankFee + $tax + $shipping,
            'storeId' => (int) $product['store_id'],
            'sellerExternalId' => (string) ($product['smartbank_external_id'] ?: 'marketplace-store-' . $product['store_id']),
            'storeName' => (string) $product['store_name'],
        ];
    }
}

