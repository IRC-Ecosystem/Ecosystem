<?php

class Auth extends Controllers
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!check_rate_limit('login_' . $ip, 5, 300)) {
                flash('error', 'Terlalu banyak percobaan login. Silakan tunggu 5 menit.');
                $this->redirect('auth/login');
            }

            $user = $this->model('User_model')->findByEmail($_POST['email'] ?? '');

            if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
                if (($user['status'] ?? 'active') === 'suspended') {
                    flash('error', 'Akun Anda ditangguhkan oleh Admin.');
                    $this->redirect('auth/login');
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                $this->redirect(role_home($user));
            }

            hit_rate_limit('login_' . $ip);
            flash('error', 'Email atau password tidak sesuai.');
        }

        $data['title'] = 'Login';
        $this->view('templates/header', $data);
        $this->view('auth/login', $data);
        $this->view('templates/footer');
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (strlen($_POST['password'] ?? '') < 6) {
                flash('error', 'Password minimal 6 karakter.');
            } elseif ($this->model('User_model')->findByEmail($_POST['email'] ?? '')) {
                flash('error', 'Email sudah terdaftar.');
            } else {
                $this->model('User_model')->create($_POST);
                flash('success', 'Akun berhasil dibuat. Silakan login.');
                $this->redirect('auth/login');
            }
        }

        $data['title'] = 'Register';
        $this->view('templates/header', $data);
        $this->view('auth/register', $data);
        $this->view('templates/footer');
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('');
    }
}
