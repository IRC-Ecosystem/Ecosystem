<?php

class Chart extends Controllers
{
    private function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    private function sellerStore(): ?array
    {
        require_role('seller');
        return $this->model('Store_model')->findByOwner(current_user()['id']);
    }

    public function adminSummary()
    {
        require_role('admin');
        $orderModel = $this->model('Order_model');
        $productModel = $this->model('Product_model');
        $stores = $this->model('Store_model')->all();

        $this->json([
            'title' => 'Kesehatan Platform',
            'updated_at' => date('Y-m-d H:i:s'),
            'items' => [
                ['label' => 'Order', 'value' => $orderModel->count(), 'color' => 'emerald'],
                ['label' => 'Produk', 'value' => $productModel->count(), 'color' => 'sky'],
                ['label' => 'Toko', 'value' => count($stores), 'color' => 'emerald'],
                ['label' => 'Stok menipis', 'value' => $productModel->lowStockCount(), 'color' => 'amber'],
            ],
        ]);
    }

    public function adminRoles()
    {
        require_role('admin');
        $items = [];
        foreach ($this->model('User_model')->countByRole() as $role) {
            $items[] = ['label' => ucfirst($role['role']), 'value' => (int) $role['total'], 'color' => 'emerald'];
        }
        $this->json(['title' => 'Distribusi Role', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function adminStores()
    {
        require_role('admin');
        $counts = [];
        foreach ($this->model('Store_model')->all() as $store) {
            $counts[$store['status']] = ($counts[$store['status']] ?? 0) + 1;
        }
        $items = [];
        foreach ($counts as $status => $count) {
            $items[] = ['label' => ucfirst($status), 'value' => $count, 'color' => $status === 'active' ? 'emerald' : 'red'];
        }
        $this->json(['title' => 'Status Toko', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function adminOrders()
    {
        require_role('admin');
        $counts = ['processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($this->model('Order_model')->all() as $order) {
            $counts[$order['order_status']] = ($counts[$order['order_status']] ?? 0) + 1;
        }
        $items = [];
        foreach ($counts as $status => $count) {
            $items[] = ['label' => ucfirst($status), 'value' => $count, 'color' => $status === 'cancelled' ? 'red' : 'emerald'];
        }
        $this->json(['title' => 'Status Order', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function sellerDashboard()
    {
        $store = $this->sellerStore();
        if (!$store) {
            $this->json(['title' => 'Produk Terlaris', 'items' => []]);
            return;
        }

        $items = array_map(fn ($product) => [
            'label' => $product['product_name'],
            'value' => (int) $product['qty_sold'],
            'color' => 'emerald',
        ], $this->model('Order_model')->bestSellersByStore($store['id']));

        $this->json(['title' => 'Produk Terlaris', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function sellerProducts()
    {
        $store = $this->sellerStore();
        $items = [];
        if ($store) {
            foreach ($this->model('Product_model')->byStore($store['id']) as $product) {
                $items[] = [
                    'label' => $product['name'],
                    'value' => (int) $product['stock'],
                    'color' => (int) $product['stock'] <= 5 ? 'amber' : 'sky',
                ];
            }
        }
        $this->json(['title' => 'Stok Produk', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function sellerOrders()
    {
        $store = $this->sellerStore();
        $counts = ['processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
        if ($store) {
            foreach ($this->model('Order_model')->byStore($store['id']) as $order) {
                $counts[$order['order_status']] = ($counts[$order['order_status']] ?? 0) + 1;
            }
        }
        $items = [];
        foreach ($counts as $status => $count) {
            $items[] = ['label' => ucfirst($status), 'value' => $count, 'color' => $status === 'cancelled' ? 'red' : 'emerald'];
        }
        $this->json(['title' => 'Status Pesanan', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function sellerFinance()
    {
        $store = $this->sellerStore();
        $summary = $store ? $this->model('Order_model')->sellerSummary($store['id']) : [];
        $revenue = (float) ($summary['total_pendapatan'] ?? 0);
        $fee = (float) ($summary['total_fee_marketplace'] ?? 0);
        $this->json([
            'title' => 'Keuangan Seller',
            'updated_at' => date('Y-m-d H:i:s'),
            'items' => [
                ['label' => 'Pendapatan kotor', 'value' => $revenue, 'formatted' => 'Rp' . number_format($revenue, 0, ',', '.'), 'color' => 'emerald'],
                ['label' => 'Fee marketplace', 'value' => $fee, 'formatted' => 'Rp' . number_format($fee, 0, ',', '.'), 'color' => 'amber'],
                ['label' => 'Estimasi bersih', 'value' => max(0, $revenue - $fee), 'formatted' => 'Rp' . number_format(max(0, $revenue - $fee), 0, ',', '.'), 'color' => 'sky'],
            ],
        ]);
    }

    public function sellerRestock()
    {
        $store = $this->sellerStore();
        $items = [];
        if ($store) {
            foreach ($this->model('Product_model')->lowStockByStore($store['id']) as $product) {
                $items[] = ['label' => $product['name'], 'value' => max(0, 5 - (int) $product['stock']), 'color' => 'amber'];
            }
        }
        $this->json(['title' => 'Prioritas Restock', 'updated_at' => date('Y-m-d H:i:s'), 'items' => $items]);
    }

    public function sellerPerformance()
    {
        $this->sellerDashboard();
    }
}
