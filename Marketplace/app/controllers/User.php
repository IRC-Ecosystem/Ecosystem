<?php

class User extends Controllers
{
    private function smartBankState(int $userId): array
    {
        $state = ['linked' => false];
        try {
            $state['linked'] = (bool) $this->model('SmartBank_model')->linkage('marketplace-user-' . $userId);
        } catch (Throwable $error) {
            $state['error'] = $error->getMessage();
            log_app_error($error);
        }
        return $state;
    }

    private function userData(string $title): array
    {
        require_role('user');
        $productModel = $this->model('Product_model');
        $minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
        $maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
        $products = $productModel->latest($_GET['q'] ?? null, $minPrice, $maxPrice);

        $orders = $this->model('Order_model')->byUser(current_user()['id']);
        $orderItems = [];
        foreach ($this->model('Order_model')->itemsByUser(current_user()['id']) as $item) {
            $orderItems[$item['order_id']][] = $item;
        }

        $smartBank = $this->smartBankState(current_user()['id']);

        $vouchers = [];
        try {
            $vouchers = $this->model('Voucher_model')->all();
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
            'user' => $this->model('User_model')->find(current_user()['id']),
            'products' => $products,
            'featured' => $productModel->featured(),
            'orders' => $orders,
            'orderItems' => $orderItems,
            'cart' => $this->model('Cart_model')->summary(),
            'categories' => $productModel->allCategories(),
            'vouchers' => $vouchers,
            'chats' => $chats,
            'smartBank' => $smartBank,
        ];
    }

    private function renderUser(string $view, string $title): void
    {
        $data = $this->userData($title);
        $this->view('templates/header', $data);
        $this->view($view, $data);
        $this->view('templates/footer');
    }

    public function index()
    {
        $this->renderUser('user/index', 'Dashboard Pembeli');
    }

    public function catalog()
    {
        $this->renderUser('user/catalog', 'Katalog Produk');
    }

    public function orders()
    {
        $this->renderUser('user/orders', 'Order Saya');
    }

    public function chat()
    {
        require_role('user');
        $userId = current_user()['id'];
        $chatModel = $this->model('Chat_model');
        $contacts = $chatModel->contactsForUser($userId);
        $activeReceiverId = isset($_GET['with']) ? (int) $_GET['with'] : ($contacts[0]['id'] ?? 2);
        $chats = $chatModel->conversation($userId, $activeReceiverId);

        $data = [
            'title' => 'Chat Bantuan & Seller',
            'user' => $this->model('User_model')->find($userId),
            'contacts' => $contacts,
            'activeReceiverId' => $activeReceiverId,
            'chats' => $chats,
        ];

        $this->view('templates/header', $data);
        $this->view('user/chat', $data);
        $this->view('templates/footer');
    }

    public function profile()
    {
        $this->renderUser('user/profile', 'Profil Pembeli');
    }

    public function cart()
    {
        require_role('user');
        $data['title'] = 'Keranjang';
        $data['summary'] = $this->model('Cart_model')->summary();
        $data['user'] = $this->model('User_model')->find(current_user()['id']);

        $this->view('templates/header', $data);
        $this->view('user/cart', $data);
        $this->view('templates/footer');
    }

    public function addCart()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $this->model('Cart_model')->add((int) ($_POST['product_id'] ?? 0), (int) ($_POST['qty'] ?? 1));
            flash('success', 'Produk masuk keranjang.');
        }
        $this->back();
    }

    public function removeCart($productId)
    {
        require_role('user');
        $this->model('Cart_model')->remove((int) $productId);
        $this->redirect('user/cart');
    }

    public function applyVoucher()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $code = trim($_POST['voucher_code'] ?? '');
            $this->model('Cart_model')->setVoucher($code);
            flash('success', 'Voucher berhasil diterapkan.');
        }
        $this->redirect('user/cart');
    }

    public function checkout()
    {
        require_role('user');
        $cart = $this->model('Cart_model');
        $summary = $cart->summary();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $address = trim($_POST['shipping_address'] ?? '');
            if ($address === '') {
                flash('error', 'Alamat pengiriman wajib diisi.');
            } else {
                $orderIds = $this->model('Order_model')->checkoutStores(current_user()['id'], $address, $summary);
                if ($orderIds) {
                    $cart->clear();
                    flash('success', count($orderIds) . ' order dibuat. Otorisasi pembayaran SmartBank dari halaman Order Saya.');
                    $this->redirect('user/orders');
                }
                flash('error', 'Checkout gagal. Cek saldo, stok, atau isi keranjang.');
            }
        }

        $data['title'] = 'Checkout';
        $data['summary'] = $summary;
        $data['user'] = $this->model('User_model')->find(current_user()['id']);
        $data['smartBank'] = $this->smartBankState(current_user()['id']);
        $this->view('templates/header', $data);
        $this->view('user/checkout', $data);
        $this->view('templates/footer');
    }

    public function cancelOrder($orderId)
    {
        require_role('user');
        if ($this->model('Order_model')->cancelOrder((int) $orderId, current_user()['id'])) {
            flash('success', 'Pesanan berhasil dibatalkan dan stok dikembalikan.');
        } else {
            flash('error', 'Gagal membatalkan pesanan.');
        }
        $this->redirect('user/orders');
    }

    public function sendChat()
    {
        require_role('user');
        $receiverId = 2;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $receiverId = (int) ($_POST['receiver_id'] ?? 2);
            $message = trim($_POST['message'] ?? '');
            if ($message !== '' && $receiverId > 0) {
                $this->model('Chat_model')->send(current_user()['id'], $receiverId, $message);
                flash('success', 'Pesan terkirim.');
            }
        }
        $this->redirect('user/chat?with=' . $receiverId);
    }

    public function smartbankOtpRequest()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $user = $this->model('User_model')->find(current_user()['id']);
            try {
                if (empty($user['phone'])) throw new RuntimeException('Nomor telepon profil wajib diisi.');
                $result = $this->model('SmartBank_model')->requestOtp($user['phone'], 'buyer-' . $user['id']);
                $_SESSION['smartbank_buyer_link'] = ['request_id' => $result['request_id']];
                flash('success', 'OTP dikirim ke Inbox SmartBank.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('user/profile');
    }

    public function smartbankOtpVerify()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $requestId = $_SESSION['smartbank_buyer_link']['request_id'] ?? '';
            $code = trim($_POST['code'] ?? '');
            try {
                if (!preg_match('/^\d{6}$/', $code) || $requestId === '') throw new RuntimeException('OTP tidak valid.');
                $result = $this->model('SmartBank_model')->verifyOtp($requestId, $code, 'buyer-' . current_user()['id']);
                $_SESSION['smartbank_buyer_link']['verification_token'] = $result['verification_token'];
                flash('success', 'OTP valid. Konfirmasi tautan wallet.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('user/profile');
    }

    public function smartbankLink()
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            try {
                $token = $_SESSION['smartbank_buyer_link']['verification_token'] ?? '';
                if ($token === '') throw new RuntimeException('Verifikasi OTP terlebih dahulu.');
                $this->model('SmartBank_model')->link('marketplace-user-' . current_user()['id'], $token);
                unset($_SESSION['smartbank_buyer_link']);
                flash('success', 'Wallet SmartBank berhasil ditautkan.');
            } catch (Throwable $error) {
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('user/profile');
    }

    public function paySmartBank($orderId)
    {
        require_role('user');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!check_rate_limit('pin_pay_' . current_user()['id'], 3, 300)) {
                flash('error', 'Terlalu banyak percobaan PIN salah. Silakan coba 5 menit lagi.');
                $this->redirect('user/orders');
            }

            try {
                $pin = trim($_POST['pin'] ?? '');
                if (!preg_match('/^\d{6}$/', $pin)) throw new RuntimeException('PIN SmartBank harus 6 digit.');
                $orders = $this->model('Order_model');
                $order = $orders->findByUser((int) $orderId, current_user()['id']);
                if (!$order || $order['payment_status'] !== 'pending') throw new RuntimeException('Order tidak siap dibayar.');
                $result = $this->model('SmartBank_model')->pay($order, current_user()['id'], $pin);
                $this->model('SmartBank_model')->recordPayment((int) $orderId, $result);
                $orders->markSmartBankPaid((int) $orderId, current_user()['id'], $result);
                $this->model('Logistics_model')->dispatchPending();
                flash('success', 'Pembayaran SmartBank berhasil.');
            } catch (Throwable $error) {
                hit_rate_limit('pin_pay_' . current_user()['id']);
                log_app_error($error);
                flash('error', $error->getMessage());
            }
        }
        $this->redirect('user/orders');
    }
}
