<?php

class Marketplace extends Controllers
{
    public function browse_produk()
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => true, 'data' => $this->model('Product_model')->latest($_GET['q'] ?? null)]);
    }

    public function checkout()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => true,
            'message' => 'Gunakan UI /user/checkout. Endpoint ini mewakili kontrak Marketplace checkout.',
            'fees' => ['marketplace' => '2%', 'gateway' => '0.5%', 'bank' => '1%', 'tax' => '2%', 'logistics' => '5% atau flat 5000'],
        ]);
    }

    public function status_order()
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => true, 'data' => is_logged_in() ? $this->model('Order_model')->byUser(current_user()['id']) : []]);
    }
}
