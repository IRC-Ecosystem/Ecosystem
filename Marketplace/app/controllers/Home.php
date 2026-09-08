<?php

class Home extends Controllers
{
    public function index()
    {
        $data['title'] = 'PasarKita - Marketplace UMKM';
        $data['products'] = $this->model('Product_model')->latest();

        $this->view('templates/header', $data);
        $this->view('home/index', $data);
        $this->view('templates/footer');
    }
}
