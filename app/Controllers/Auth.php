<?php

namespace App\Controllers;

use App\Models\AdminModel;

class Auth extends BaseController
{
    protected AdminModel $adminModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
    }

    public function logout()
    {
        $session = session();
        $session->destroy();

        // Hapus cookie remember me jika ada
        $response = service('response');
        $response->deleteCookie('cmsinvestorid', '', '/');

        return redirect()->to('/login')->with('success', 'You have been logged out from Investor.id CMS workspace.');
    }

    public function login()
    {
        $session = session();

        // If already logged in, redirect directly to dashboard
        if ($session->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        // If currently in pending 2FA verification, redirect to verify screen
        if ($session->has('pending_2fa_user_id')) {
            return redirect()->to('/auth/verify-code');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim($this->request->getPost('username') ?? $this->request->getPost('email') ?? '');
            $password = trim($this->request->getPost('password') ?? '');

            if (empty($username) || empty($password)) {
                return redirect()->to('/login')->with('error', 'Silakan masukkan username dan kata sandi.');
            }

            $user = $this->adminModel->authenticate($username, $password);

            if ($user) {
                $now = new \DateTime();
                $nextVerify = !empty($user['nextVerify']) ? new \DateTime($user['nextVerify']) : null;

                // Cek apakah verifikasi 2FA masih berlaku (< 5 hari) sesuai aturan legacy
                if ($nextVerify && $nextVerify > $now) {
                    $this->setAuthenticatedSession($user);

                    return redirect()->to('/dashboard')->with('success', 'Selamat datang kembali, ' . esc($user['name']) . '!');
                }

                // Butuh 2FA: generate kode baru 6-digit acak
                $authCode = random_int(100000, 999999);
                $authExpired = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $this->adminModel->update($user['userID'] ?? $user['id'], [
                    'authCode'    => (string)$authCode,
                    'authExpired' => $authExpired,
                ]);

                // Kirim email via Brevo SMTP
                $this->sendAuthCodeEmail($user, $authCode);

                // Set session pending 2FA
                $session->set([
                    'pending_2fa_user_id' => $user['userID'] ?? $user['id'],
                    'pending_2fa_email'   => $user['email'] ?? '',
                    'pending_2fa_name'    => $user['name'] ?: ($user['username'] ?: 'Editor'),
                ]);

                return redirect()->to('/auth/verify-code')->with('info', 'Kode autentifikasi telah dikirimkan ke email ' . $this->maskEmail($user['email'] ?? ''));
            }

            return redirect()->to('/login')->with('error', 'Kredensial tidak valid. Silakan periksa username atau kata sandi Anda.');
        }

        // Demo users for quick selection in development login screen
        $demoUsers = $this->adminModel->select('
                userID AS id,
                name,
                username,
                email,
                roleName AS role
            ')
            ->findAll();

        return view('auth/login', ['demoUsers' => $demoUsers]);
    }

    /**
     * GET/POST /auth/verify-code
     * Halaman input dan validasi kode 6-digit 2FA yang dikirim ke email
     * Replika legacy: pages/login/auth-verification.php
     */
    public function verifyCode()
    {
        $session = session();
        $pendingUserId = $session->get('pending_2fa_user_id');

        if (!$pendingUserId) {
            return redirect()->to('/login');
        }

        $user = $this->adminModel->find($pendingUserId);
        if (!$user) {
            $session->remove(['pending_2fa_user_id', 'pending_2fa_email', 'pending_2fa_name']);
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid.');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $code = trim(preg_replace('/[^0-9]/', '', (string)$this->request->getPost('auth_code')));

            if (empty($code)) {
                return redirect()->to('/auth/verify-code')->with('error', 'Silakan masukkan 6-digit kode autentifikasi.');
            }

            $nowStr = date('Y-m-d H:i:s');
            $userAuthCode = (string)($user['authCode'] ?? '');
            $userAuthExpired = (string)($user['authExpired'] ?? '');

            if ($userAuthCode !== '' && $userAuthCode === $code && ($userAuthExpired === '' || $userAuthExpired >= $nowStr)) {
                // Kode cocok & valid: perpanjang nextVerify +5 hari sesuai legacy
                $this->adminModel->update($user['userID'] ?? $user['id'], [
                    'authCode'    => null,
                    'authExpired' => null,
                    'nextVerify'  => date('Y-m-d', strtotime('+5 days')),
                    'lastLogin'   => date('Y-m-d H:i:s'),
                    'lastLoginIP' => $this->request->getIPAddress(),
                ]);

                $session->remove(['pending_2fa_user_id', 'pending_2fa_email', 'pending_2fa_name']);
                $this->setAuthenticatedSession($user);

                return redirect()->to('/dashboard')->with('success', 'Autentifikasi berhasil! Selamat datang, ' . esc($user['name']) . '.');
            }

            return redirect()->to('/auth/verify-code')->with('error', 'Kode autentifikasi salah atau sudah kadaluarsa. Silakan periksa kembali.');
        }

        return view('auth/verify_code', [
            'title'       => 'Verifikasi Kode Autentifikasi',
            'maskedEmail' => $this->maskEmail($user['email'] ?? ''),
            'user'        => $user,
        ]);
    }

    /**
     * POST /auth/resend-code
     * Mengirim ulang 6-digit auth code baru ke email user
     */
    public function resendCode()
    {
        $session = session();
        $pendingUserId = $session->get('pending_2fa_user_id');

        if (!$pendingUserId) {
            return redirect()->to('/login');
        }

        $user = $this->adminModel->find($pendingUserId);
        if (!$user) {
            return redirect()->to('/login');
        }

        $authCode = random_int(100000, 999999);
        $authExpired = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $this->adminModel->update($user['userID'] ?? $user['id'], [
            'authCode'    => (string)$authCode,
            'authExpired' => $authExpired,
        ]);

        $this->sendAuthCodeEmail($user, $authCode);

        return redirect()->to('/auth/verify-code')->with('success', 'Kode baru telah berhasil dikirimkan ke email ' . $this->maskEmail($user['email'] ?? ''));
    }

    /**
     * Helper: Menyetel session login penuh setelah kredensial & 2FA valid
     */
    protected function setAuthenticatedSession(array $user): void
    {
        $displayName = $user['name'] ?? '';
        $nameParts = explode(' ', $displayName);
        $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

        session()->set([
            'user_id'       => (int)($user['userID'] ?? $user['id']),
            'user_name'     => $displayName,
            'user_email'    => $user['email'] ?? '',
            'user_role'     => $user['roleName'] ?? $user['role'] ?? 'administrator',
            'user_username' => $user['username'] ?? '',
            'user_alias'    => $user['alias'] ?? '',
            'user_initials' => $initials,
            'password_expired' => $user['passwordExpired'] ?? null,
        ]);
    }

    /**
     * Helper: Masking alamat email (contoh: raka@investor.id -> ra***@investor.id)
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) < 2) return $email;
        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);
        if ($len <= 2) {
            return substr($name, 0, 1) . '***@' . $domain;
        }
        $visible = (int)ceil($len / 3);
        return substr($name, 0, $visible) . str_repeat('*', $len - $visible) . '@' . $domain;
    }

    /**
     * Helper: Mengirim email kode autentifikasi 2FA via Brevo SMTP
     */
    protected function sendAuthCodeEmail(array $user, int $authCode): bool
    {
        try {
            $email = \Config\Services::email();
            $email->setTo($user['email']);
            $email->setSubject('Kode Autentifikasi - CMS Investor');

            $body = '<div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 28px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff; color: #1e293b;">';
            $body .= '<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">';
            $body .= '<h2 style="color: #1A365D; margin: 0; font-size: 20px; font-weight: 700;">CMS Investor.id</h2>';
            $body .= '<p style="color: #64748B; font-size: 12px; margin: 4px 0 0 0;">Two-Factor Authentication (2FA)</p>';
            $body .= '</div>';
            $body .= '<p style="font-size: 14px; line-height: 1.5;">Halo, <strong>' . esc($user['name'] ?: $user['username']) . '</strong>,</p>';
            $body .= '<p style="font-size: 14px; line-height: 1.5; color: #334155;">Berikut adalah kode autentifikasi 6-digit untuk melanjutkan proses login ke CMS Investor:</p>';
            $body .= '<div style="text-align: center; margin: 24px 0;">';
            $body .= '<span style="display: inline-block; font-size: 32px; font-weight: 800; font-family: monospace; letter-spacing: 8px; padding: 14px 32px; background-color: #EBF8FF; color: #2B6CB0; border-radius: 8px; border: 1.5px dashed #2B6CB0;">' . $authCode . '</span>';
            $body .= '</div>';
            $body .= '<p style="color: #64748B; font-size: 13px; line-height: 1.5;">Kode verifikasi ini berlaku selama <strong>15 menit</strong>. Masukkan kode ini pada layar verifikasi login.</p>';
            $body .= '<p style="color: #64748B; font-size: 13px; line-height: 1.5;">Abaikan pesan ini jika Anda tidak merasa melakukan percobaan login, dan segera hubungi Administrator IT.</p>';
            $body .= '<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">';
            $body .= '<p style="color: #94a3b8; font-size: 11px; margin: 0; text-align: center;">&copy; ' . date('Y') . ' Investor.ID Editorial Newsroom</p>';
            $body .= '</div>';

            $email->setMessage($body);
            $sent = $email->send();
            if (!$sent) {
                log_message('error', 'Gagal mengirim email 2FA via Brevo: ' . $email->printDebugger(['headers', 'subject', 'body']));
            }
            return $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengirim email 2FA: ' . $e->getMessage());
            return false;
        }
    }
}
