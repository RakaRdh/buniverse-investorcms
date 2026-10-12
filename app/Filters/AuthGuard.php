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
            // Cek cookie remember me cmsinvestorid (HMAC signed token dengan fallback legacy salt)
            $cookieVal = $request->getCookie('cmsinvestorid');
            if (!empty($cookieVal)) {
                $adminModel = new \App\Models\AdminModel();
                $user = null;

                // 1. Coba decode HMAC signed token: base64(userId|expiry|sig)
                $decoded = base64_decode($cookieVal, true);
                if ($decoded && substr_count($decoded, '|') >= 2) {
                    $parts = explode('|', $decoded);
                    if (count($parts) === 3) {
                        [$cUserId, $cExpiry, $cSig] = $parts;
                        if ((int)$cExpiry >= time()) {
                            $rawPayload = $cUserId . '|' . $cExpiry;
                            $expectedSig = hash_hmac('sha256', $rawPayload, \App\Models\AdminModel::getPepper());
                            if (hash_equals($expectedSig, $cSig)) {
                                $user = $adminModel->where('active', 1)->find((int)$cUserId);
                            }
                        }
                    }
                }

                // 2. Fallback legacy jika user lama masih memiliki cookie salt mentah
                if (!$user) {
                    $user = $adminModel->where('active', 1)->where('salt', $cookieVal)->first();
                }

                if ($user) {
                    $now = new \DateTime();
                    $nextVerify = !empty($user['nextVerify']) ? new \DateTime($user['nextVerify']) : null;

                    if (!$nextVerify || $nextVerify <= $now) {
                        $response = service('response');
                        $response->deleteCookie('cmsinvestorid', '', '/');
                        $response->deleteCookie('cmsinvestorid2019', '', '/');
                    } else {
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
        }

        if (!$session->has('user_id')) {
            $uri = uri_string();
            if ($request->isAJAX() || strpos($uri, 'api/') === 0) {
                return service('response')->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized: Sesi login diperlukan untuk mengakses layanan ini.'
                ])->setStatusCode(401);
            }
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
