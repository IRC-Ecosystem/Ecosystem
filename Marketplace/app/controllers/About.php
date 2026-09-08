<?php

class About extends Controllers
{
    public function index()
    {
        $data['title'] = 'Tentang PasarKita';
        $this->view('templates/header', $data);
        $this->view('about/index', $data);
        $this->view('templates/footer');
    }
}
