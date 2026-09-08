<?php

class Toko extends Controllers
{
    private function store(): ?array
    {
        return $this->model('Store_model')->findByOwner(current_user()['id']);
    }

    private function sellerData(string $title): array
    {
        require_role('seller');
        $store = $this->store();
        if (!$store) {
            $this->redirect('toko');
        }

        $productModel = $this->model('Product_model');
        $orderModel = $this->model('Order_model');

        $promotions = [];
        try {
            $promotions = $this->model('Voucher_model')->all();
        } catch (Throwable $e) {
            log_app_error($e);
        }

        $chats = [];
        try {
            $chats = $this->model('Chat_model')->forUser(current_user()['id']);
        } catch (Throwable $e) {
            log_app_error($e);
        }

        return [
            'title' => $title,
            'store' => $store,
            'products' => $productModel->byStore($store['id']),
            'orders' => $orderModel->byStore($store['id']),
            'orderItems' => $orderModel->itemsByStore($store['id']),
            'summary' => $orderModel->sellerSummary($store['id']),
            'bestSellers' => $orderModel->bestSellersByStore($store['id']),
            'lowStock' => $productModel->lowStockByStore($store['id']),
            'categories' => $productModel->categoriesByStore($store['id']),
            'promotions' => $promotions,
            'messages' => $chats,
        ];
    }

    private function renderSeller(string $view, string $title): void
    {
        $data = $this->sellerData($title);
        $this->view('templates/header', $data);
        $this->view($view, $data);
        $this->view('templates/footer');
    }

    public function index()
    {
        require_login();
        if (has_role('admin')) {
            $this->redirect('admin');
        }

        $store = $this->store();
        if (!$store) {
            $data['title'] = 'Buka Toko';
            $data['store'] = null;
            $this->view('templates/header', $data);
            $this->view('toko/index', $data);
            $this->view('templates/footer');
            return;
        }

        $this->renderSeller('toko/dashboard', 'Dashboard Seller');
    }

    public function products()
    {
        $this->renderSeller('toko/products', 'Manajemen Produk');
    }

    public function orders()
    {
        $this->renderSeller('toko/orders', 'Manajemen Pesanan');
    }

    public function promotions()
    {
        $this->renderSeller('toko/promotions', 'Promosi Toko');
    }

    public function chat()
    {
        require_role('seller');
        $user = current_user();
        $store = $this->store();
        if (!$store) {
            $this->redirect('toko');
        }

        $chatModel = $this->model('Chat_model');
        $contacts = $chatModel->contactsForUser($user['id']);
        $activeReceiverId = isset($_GET['with']) ? (int) $_GET['with'] : ($contacts[0]['id'] ?? 3);
        $chats = $chatModel->conversation($user['id'], $activeReceiverId);

        $data = [
            'title' => 'Chat Pembeli',
            'store' => $store,
            'contacts' => $contacts,
            'activeReceiverId' => $activeReceiverId,
            'chats' => $chats,
        ];

        $this->view('templates/header', $data);
        $this->view('toko/chat', $data);
        $this->view('templates/footer');
    }

    public function finance()
    {
        $this->renderSeller('toko/finance', 'Keuangan Seller');
    }

    public function restock()
    {
        $this->renderSeller('toko/restock', 'Restock SupplierHub');
    }

    public function performance()
    {
        $this->renderSeller('toko/performance', 'Performa Toko');
    }

    public function smartbank()
    {
        require_role('seller');
        $store = $this->store();
        $state = ['linked' => false, 'request_id' => $_SESSION['smartbank_store_link']['request_id'] ?? null, 'verified' => !empty($_SESSION['smartbank_store_link']['verification_token'])];
        try {
            $state['linked'] = (bool) $this->model('SmartBank_model')->linkage($store['smartbank_external_id'] ?: 'marketplace-store-' . $store['id']);
        } catch (Throwable $error) {
            $state['error'] = $error->getMessage();
        }
        $data = ['title' => 'Wallet SmartBank Toko', 'store' => $store, 'smartBank' => $state];
        $this->view('templates/header', $data);
        $this->view('toko/smartbank', $data);
        $this->view('templates/footer');
    }

    public function smartbankOtpRequest()
    {
        require_role('seller');
        try {
            $phone = trim($_POST['phone'] ?? '');
            if ($phone === '') throw new RuntimeException('Nomor SmartBank wajib diisi.');
            $result = $this->model('SmartBank_model')->requestOtp($phone, 'store-' . current_user()['id']);
            $_SESSION['smartbank_store_link'] = ['request_id' => $result['request_id']];
            flash('success', 'OTP dikirim ke Inbox SmartBank.');
        } catch (Throwable $error) { flash('error', $error->getMessage()); }
        $this->redirect('toko/smartbank');
    }

    public function smartbankOtpVerify()
    {
        require_role('seller');
        try {
            $requestId = $_SESSION['smartbank_store_link']['request_id'] ?? '';
            $code = trim($_POST['code'] ?? '');
            if ($requestId === '' || !preg_match('/^\\d{6}$/', $code)) throw new RuntimeException('OTP tidak valid.');
            $result = $this->model('SmartBank_model')->verifyOtp($requestId, $code, 'store-' . current_user()['id']);
            $_SESSION['smartbank_store_link']['verification_token'] = $result['verification_token'];
            flash('success', 'OTP valid. Konfirmasi wallet toko.');
        } catch (Throwable $error) { flash('error', $error->getMessage()); }
        $this->redirect('toko/smartbank');
    }

    public function smartbankLink()
    {
        require_role('seller');
        try {
            $store = $this->store();
            $token = $_SESSION['smartbank_store_link']['verification_token'] ?? '';
            if ($token === '') throw new RuntimeException('Verifikasi OTP terlebih dahulu.');
            $externalId = $store['smartbank_external_id'] ?: 'marketplace-store-' . $store['id'];
            $this->model('SmartBank_model')->link($externalId, $token);
            $this->model('Store_model')->setSmartBankExternalId((int) $store['id'], $externalId);
            unset($_SESSION['smartbank_store_link']);
            flash('success', 'Wallet toko berhasil ditautkan.');
        } catch (Throwable $error) { flash('error', $error->getMessage()); }
        $this->redirect('toko/smartbank');
    }

    public function create()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (trim($_POST['name'] ?? '') === '') {
                flash('error', 'Nama toko wajib diisi.');
            } elseif ($this->model('Store_model')->create(current_user()['id'], $_POST)) {
                $_SESSION['user']['role'] = 'seller';
                flash('success', 'Toko berhasil dibuat. Kamu sekarang punya akses seller.');
            } else {
                flash('error', 'Gagal membuat toko.');
            }
        }
        $this->redirect('toko');
    }

    public function product()
    {
        require_role('seller');
        $store = $this->store();
        if (!$store) {
            $this->redirect('toko');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $required = ['name', 'category', 'price', 'stock'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
                    flash('error', 'Data produk belum lengkap.');
                    $this->redirect('toko/products');
                }
            }
            $this->model('Product_model')->create($store['id'], $_POST);
            flash('success', 'Produk berhasil ditambahkan.');
        }

        $this->redirect('toko/products');
    }

    public function updateProduct($id)
    {
        require_role('seller');
        $store = $this->store();
        if ($store && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $this->model('Product_model')->update((int) $id, $store['id'], $_POST);
            flash('success', 'Produk diperbarui.');
        }
        $this->redirect('toko/products');
    }

    public function deleteProduct($id)
    {
        require_role('seller');
        $store = $this->store();
        if ($store) {
            $this->model('Product_model')->delete((int) $id, $store['id']);
            flash('success', 'Produk dihapus.');
        }
        $this->redirect('toko/products');
    }

    public function orderStatus()
    {
        require_role('seller');
        $store = $this->store();
        if ($store && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $this->model('Order_model')->updateStatus((int) $_POST['order_id'], $_POST['status'], $store['id']);
            flash('success', 'Status pesanan toko Anda berhasil diperbarui.');
        }
        $this->redirect('toko/orders');
    }

    public function sendChat()
    {
        require_role('seller');
        $store = $this->store();
        $receiverId = 3;
        if ($store && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $receiverId = (int) ($_POST['receiver_id'] ?? 3); // Default to buyer
            $message = trim($_POST['message'] ?? '');
            if ($message !== '' && $receiverId > 0) {
                $this->model('Chat_model')->send(current_user()['id'], $receiverId, $message, $store['id']);
                flash('success', 'Pesan balasan terkirim.');
            }
        }
        $this->redirect('toko/chat?with=' . $receiverId);
    }
}
