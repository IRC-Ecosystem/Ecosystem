<?php

class Admin extends Controllers
{
    private function adminData(string $title): array
    {
        require_role('admin');
        $userModel = $this->model('User_model');
        $orderModel = $this->model('Order_model');
        $productModel = $this->model('Product_model');

        $stores = $this->model('Store_model')->all();
        $users = $userModel->all();
        $orders = $orderModel->all();
        $productCountsByStore = [];
        foreach ($productModel->countByStore() as $row) {
            $productCountsByStore[(int) $row['store_id']] = (int) $row['total'];
        }
        $revenueByStore = [];
        foreach ($orderModel->storeRevenueSummary() as $row) {
            $revenueByStore[(int) $row['store_id']] = [
                'revenue' => (float) $row['revenue'],
                'orders' => (int) $row['orders'],
            ];
        }
        $storeMetrics = [];
        foreach ($stores as $store) {
            $storeId = (int) $store['id'];
            $storeMetrics[$storeId] = [
                'products' => $productCountsByStore[$storeId] ?? 0,
                'revenue' => $revenueByStore[$storeId]['revenue'] ?? 0,
                'orders' => $revenueByStore[$storeId]['orders'] ?? 0,
            ];
        }
        $roleCounts = [];
        foreach ($userModel->countByRole() as $role) {
            $roleCounts[$role['role']] = (int) $role['total'];
        }
        $orderStatusCounts = [];
        foreach ($orders as $order) {
            $orderStatusCounts[$order['order_status']] = ($orderStatusCounts[$order['order_status']] ?? 0) + 1;
        }
        $storeStatusCounts = [];
        foreach ($stores as $store) {
            $storeStatusCounts[$store['status']] = ($storeStatusCounts[$store['status']] ?? 0) + 1;
        }

        return [
            'title' => $title,
            'users' => $users,
            'roles' => $userModel->countByRole(),
            'stores' => $stores,
            'store_metrics' => $storeMetrics,
            'orders' => $orders,
            'low_stock_products' => $productModel->lowStockGlobal(),
            'stats' => [
                'orders' => $orderModel->count(),
                'revenue' => $orderModel->revenue(),
                'products' => $productModel->count(),
                'low_stock' => $productModel->lowStockCount(),
                'stores' => count($stores),
            ],
            'charts' => [
                'roles' => $roleCounts,
                'order_status' => $orderStatusCounts,
                'store_status' => $storeStatusCounts,
                'platform_summary' => [
                    'orders' => $orderModel->count(),
                    'products' => $productModel->count(),
                    'stores' => count($stores),
                    'low_stock' => $productModel->lowStockCount(),
                ],
            ],
        ];
    }

    private function renderAdmin(string $view, string $title): void
    {
        $data = $this->adminData($title);
        $this->view('templates/header', $data);
        $this->view($view, $data);
        $this->view('templates/footer');
    }

    public function index()
    {
        $this->renderAdmin('admin/index', 'Dashboard Admin');
    }

    public function users()
    {
        $this->renderAdmin('admin/users', 'Manajemen User');
    }

    public function toggleUserStatus($id)
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if ($this->model('User_model')->toggleStatus((int) $id)) {
                flash('success', 'Status user berhasil diperbarui.');
            } else {
                flash('error', 'Gagal memperbarui status user.');
            }
        }
        $this->redirect('admin/users');
    }

    public function stores()
    {
        $this->renderAdmin('admin/stores', 'Monitoring Toko');
    }

    public function orders()
    {
        $this->renderAdmin('admin/orders', 'Monitoring Order');
    }

    public function sellerFeatures()
    {
        $this->renderAdmin('admin/seller_features', 'Fitur Penjual');
    }

    public function analytics()
    {
        $this->renderAdmin('admin/analytics', 'Analitik');
    }

    public function smartbank()
    {
        require_role('admin');
        $state = ['linked' => false, 'request_id' => $_SESSION['smartbank_marketplace_link']['request_id'] ?? null, 'verified' => !empty($_SESSION['smartbank_marketplace_link']['verification_token'])];
        try {
            $state['linked'] = (bool) $this->model('SmartBank_model')->linkage(SMARTBANK_MARKETPLACE_EXTERNAL_ID);
        } catch (Throwable $error) {
            $state['error'] = $error->getMessage();
            log_app_error($error);
        }
        $data = ['title' => 'Wallet SmartBank Marketplace', 'smartBank' => $state];
        $this->view('templates/header', $data);
        $this->view('admin/smartbank', $data);
        $this->view('templates/footer');
    }

    public function smartbankOtpRequest()
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            try {
                $phone = trim($_POST['phone'] ?? '');
                if ($phone === '') throw new RuntimeException('Nomor SmartBank wajib diisi.');
                $result = $this->model('SmartBank_model')->requestOtp($phone, 'marketplace');
                $_SESSION['smartbank_marketplace_link'] = ['request_id' => $result['request_id']];
                flash('success', 'OTP dikirim ke Inbox SmartBank.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('admin/smartbank');
    }

    public function smartbankOtpVerify()
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            try {
                $requestId = $_SESSION['smartbank_marketplace_link']['request_id'] ?? '';
                $code = trim($_POST['code'] ?? '');
                if ($requestId === '' || !preg_match('/^\d{6}$/', $code)) throw new RuntimeException('OTP tidak valid.');
                $result = $this->model('SmartBank_model')->verifyOtp($requestId, $code, 'marketplace');
                $_SESSION['smartbank_marketplace_link']['verification_token'] = $result['verification_token'];
                flash('success', 'OTP valid. Konfirmasi wallet penerima.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('admin/smartbank');
    }

    public function smartbankLink()
    {
        require_role('admin');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            try {
                $token = $_SESSION['smartbank_marketplace_link']['verification_token'] ?? '';
                if ($token === '') throw new RuntimeException('Verifikasi OTP terlebih dahulu.');
                $this->model('SmartBank_model')->link(SMARTBANK_MARKETPLACE_EXTERNAL_ID, $token);
                unset($_SESSION['smartbank_marketplace_link']);
                flash('success', 'Wallet penerima Marketplace berhasil ditautkan.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('admin/smartbank');
    }
}
