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
            // Cek cookie remember me cmsinvestorid sesuai standar legacy
            $cookieSalt = $request->getCookie('cmsinvestorid');
            if (!empty($cookieSalt)) {
                $adminModel = new \App\Models\AdminModel();
                $user = $adminModel->where('active', 1)->where('salt', $cookieSalt)->first();
                if ($user) {
                    $displayName = $user['name'] ?? '';
                    $nameParts = explode(' ', $displayName);
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

                    $session->set([
                        'user_id'          => (int)($user['userID'] ?? $user['id']),
                        'user_name'        => $displayName,
                        'user_email'       => $user['email'] ?? '',
                        'user_role'        => $user['roleName'] ?? $user['role'] ?? 'administrator',
                        'user_username'    => $user['username'] ?? '',
                        'user_alias'       => $user['alias'] ?? '',
                        'user_initials'    => $initials,
                        'password_expired' => $user['passwordExpired'] ?? null,
                    ]);
                }
            }
        }

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
