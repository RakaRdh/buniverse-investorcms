<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu untuk mengakses CMS.');
        }

        // Cek password expiry sesuai aturan legacy login.php
        $pwdExpired = $session->get('password_expired');
        if (!empty($pwdExpired)) {
            $nowStr = date('Y-m-d');
            $currentUri = uri_string();
            // Jika tanggal passwordExpired sudah lewat dan user belum berada di /user/profile
            if ($pwdExpired < $nowStr && strpos($currentUri, 'user/profile') === false && strpos($currentUri, 'logout') === false) {
                return redirect()->to('/user/profile')->with('warning', 'Masa berlaku kata sandi Anda telah habis. Silakan perbarui kata sandi akun Anda demi keamanan.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
