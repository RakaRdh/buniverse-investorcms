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
                return redirect()->to('/login')->withInput()->with('error', 'Silakan masukkan username dan kata sandi.');
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

            return redirect()->to('/login')->withInput()->with('error', 'Kredensial tidak valid. Silakan periksa username atau kata sandi Anda.');
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
                // Kode cocok & valid: perpanjang nextVerify +5 hari sesuai legacy (pertahankan authCode & authExpired)
                $this->adminModel->update($user['userID'] ?? $user['id'], [
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

        // Standar legacy: set cookie cmsinvestorid berbasis salt unik user (24 jam)
        if (!empty($user['salt'])) {
            $response = service('response');
            $response->setCookie([
                'name'     => 'cmsinvestorid',
                'value'    => $user['salt'],
                'expire'   => 86400, // 24 jam
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
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

    /**
     * GET /auth/forgot-password
     * Halaman form permintaan reset kata sandi
     */
    public function forgotPassword()
    {
        if (session()->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/forgot_password', [
            'title' => 'Lupa Kata Sandi — Investor.ID Newsroom CMS'
        ]);
    }

    /**
     * POST /auth/forgot-password
     * Kirim link tautan token reset unik sekali pakai (one-time token) ke email user
     * Masa berlaku token: 15 menit, tersimpan aman di kolom authCode & authExpired
     */
    public function sendResetLink()
    {
        $identifier = trim($this->request->getPost('identifier') ?? $this->request->getPost('email') ?? '');

        if (empty($identifier)) {
            return redirect()->to('/auth/forgot-password')->with('error', 'Silakan masukkan username atau alamat email internal Anda.');
        }

        $user = $this->adminModel->where('active', 1)
            ->groupStart()
                ->where('username', $identifier)
                ->orWhere('email', $identifier)
            ->groupEnd()
            ->first();

        // Standar Keamanan Celah #2: Anti-Account Enumeration
        // Selalu tampilkan pesan umum yang sama agar penyerang tidak bisa men-scrape username staf yang valid
        if (!$user) {
            return redirect()->to('/auth/forgot-password')
                ->with('info', 'Jika username atau email terdaftar dalam sistem redaksi, tautan pemulihan kata sandi telah dikirimkan ke email Anda.');
        }

        // Celah #1 & #3: Gunakan 6-digit numeric secure OTP yang kompatibel dengan kolom INT(11) di database
        $resetToken = (string)random_int(100000, 999999);
        $authExpired = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Simpan token unik ke kolom authCode & authExpired bawaan tabel
        $this->adminModel->update($user['userID'] ?? $user['id'], [
            'authCode'    => (int)$resetToken,
            'authExpired' => $authExpired,
        ]);

        $this->sendPasswordResetEmail($user, $resetToken);

        return redirect()->to('/auth/forgot-password')
            ->with('info', 'Tautan dan kode pemulihan kata sandi telah dikirimkan ke email ' . $this->maskEmail($user['email'] ?? '') . '. Silakan periksa kotak masuk atau spam Anda.');
    }

    /**
     * GET /auth/reset-password?token=...&user=...
     * POST /auth/reset-password
     * Verifikasi token tautan email sekali pakai dan set kata sandi baru (SHA-512 salted hash)
     */
    public function resetPassword()
    {
        if (session()->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        $tokenParam = $this->request->getGet('token') ?: $this->request->getPost('token') ?: '';
        $userParam = $this->request->getGet('user') ?: $this->request->getPost('username') ?: '';

        if (strtolower($this->request->getMethod()) === 'post') {
            $token = trim((string)$this->request->getPost('token'));
            $identifier = trim((string)$this->request->getPost('username'));
            $newPassword = trim((string)$this->request->getPost('new_password'));
            $confirmPassword = trim((string)$this->request->getPost('confirm_password'));

            if (empty($token) || empty($identifier) || empty($newPassword)) {
                return redirect()->to('/auth/reset-password?token=' . urlencode($token) . '&user=' . urlencode($identifier))
                    ->with('error', 'Semua kolom formulir wajib diisi.');
            }

            $passwordErrors = AdminModel::validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                return redirect()->to('/auth/reset-password?token=' . urlencode($token) . '&user=' . urlencode($identifier))
                    ->with('error', 'Kombinasi kata sandi belum memenuhi standar: ' . implode(' ', $passwordErrors));
            }

            if ($newPassword !== $confirmPassword) {
                return redirect()->to('/auth/reset-password?token=' . urlencode($token) . '&user=' . urlencode($identifier))
                    ->with('error', 'Konfirmasi kata sandi baru tidak cocok.');
            }

            $user = $this->adminModel->where('active', 1)
                ->groupStart()
                    ->where('username', $identifier)
                    ->orWhere('email', $identifier)
                ->groupEnd()
                ->first();

            if (!$user) {
                return redirect()->to('/auth/forgot-password')
                    ->with('error', 'Tautan pemulihan tidak valid atau akun tidak ditemukan.');
            }

            $nowStr = date('Y-m-d H:i:s');
            $userAuthCode = (string)($user['authCode'] ?? '');
            $userAuthExpired = (string)($user['authExpired'] ?? '');

            // Verifikasi token sekali pakai & masa berlaku 15 menit
            if ($userAuthCode === '' || !hash_equals($userAuthCode, $token) || ($userAuthExpired !== '' && $userAuthExpired < $nowStr)) {
                return redirect()->to('/auth/forgot-password')
                    ->with('error', 'Tautan pemulihan kata sandi sudah tidak berlaku atau kadaluarsa. Silakan ajukan permohonan baru.');
            }

            // Valid: hash kata sandi baru menggunakan SHA-512 + 128 chars salt (standar produksi CI4 & Legacy)
            $username = $user['username'] ?? '';
            $newSalt = AdminModel::generateSalt($username, $newPassword);
            $newHash = AdminModel::encodePassword($newPassword, $newSalt);

            // Segera hanguskan token pemulihan (One-Time Token) dan set rotasi 90 hari
            $this->adminModel->update($user['userID'] ?? $user['id'], [
                'password'        => $newHash,
                'salt'            => $newSalt,
                'authCode'        => null,
                'authExpired'     => null,
                'passwordExpired' => date('Y-m-d H:i:s', strtotime('+90 days')),
            ]);

            return redirect()->to('/login')->with('success', 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.');
        }

        // Tampilan GET: jika ada parameter token & user, lakukan pre-validation awal
        $isValidLink = false;
        if (!empty($tokenParam) && !empty($userParam)) {
            $user = $this->adminModel->where('active', 1)
                ->groupStart()
                    ->where('username', $userParam)
                    ->orWhere('email', $userParam)
                ->groupEnd()
                ->first();

            if ($user && !empty($user['authCode']) && hash_equals($user['authCode'], $tokenParam)) {
                $nowStr = date('Y-m-d H:i:s');
                if (empty($user['authExpired']) || $user['authExpired'] >= $nowStr) {
                    $isValidLink = true;
                }
            }
        }

        return view('auth/reset_password', [
            'title'         => 'Reset Kata Sandi — Investor.ID Newsroom CMS',
            'prefilledUser' => $userParam,
            'token'         => $tokenParam,
            'isValidLink'   => $isValidLink
        ]);
    }

    /**
     * Helper: Kirim email instruksi link reset kata sandi unik via Brevo SMTP
     */
    protected function sendPasswordResetEmail(array $user, string $resetToken): bool
    {
        try {
            $email = \Config\Services::email();
            $email->setTo($user['email']);
            $email->setSubject('Tautan Pemulihan Kata Sandi - CMS Investor');

            $resetUrl = site_url('auth/reset-password?token=' . urlencode($resetToken) . '&user=' . urlencode($user['username']));

            $body = '<div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 28px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff; color: #1e293b;">';
            $body .= '<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">';
            $body .= '<h2 style="color: #1A365D; margin: 0; font-size: 20px; font-weight: 700;">CMS Investor.id</h2>';
            $body .= '<p style="color: #64748B; font-size: 12px; margin: 4px 0 0 0;">Pemulihan Kredensial Editorial</p>';
            $body .= '</div>';
            $body .= '<p style="font-size: 14px; line-height: 1.5;">Halo, <strong>' . esc($user['name'] ?: $user['username']) . '</strong>,</p>';
            $body .= '<p style="font-size: 14px; line-height: 1.5; color: #334155;">Kami menerima permintaan untuk mereset kata sandi akun editorial Anda. Silakan klik tombol di bawah ini untuk langsung membuat kata sandi baru:</p>';
            $body .= '<div style="text-align: center; margin: 28px 0;">';
            $body .= '<a href="' . $resetUrl . '" style="display: inline-block; font-size: 14px; font-weight: 700; color: #ffffff; background-color: #0077CC; padding: 14px 32px; border-radius: 8px; text-decoration: none; box-shadow: 0 3px 6px rgba(0,119,204,0.25);">Buat Kata Sandi Baru</a>';
            $body .= '</div>';
            $body .= '<div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 12px; color: #475569;">';
            $body .= '<strong style="color: #1e293b; display: block; margin-bottom: 4px;">Tombol tidak dapat diklik?</strong>';
            $body .= 'Salin tautan ini ke peramban Anda:<br><a href="' . $resetUrl . '" style="color: #0077CC; word-break: break-all; text-decoration: underline;">' . $resetUrl . '</a><br><br>';
            $body .= 'Atau jika diminta memasukkan kode secara manual, gunakan kode token: <strong style="font-family: monospace; font-size: 14px; color: #0077CC; letter-spacing: 2px;">' . $resetToken . '</strong>';
            $body .= '</div>';
            $body .= '<p style="color: #ef4444; font-size: 12px; line-height: 1.5; font-weight: 600;">Tautan ini hanya berlaku selama 15 menit dan hanya dapat digunakan 1 (satu) kali.</p>';
            $body .= '<p style="color: #64748B; font-size: 12px; line-height: 1.5;">Jika Anda tidak merasa mengajukan permintaan ini, abaikan email ini dan kata sandi Anda tetap aman.</p>';
            $body .= '<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">';
            $body .= '<p style="color: #94a3b8; font-size: 11px; margin: 0; text-align: center;">&copy; ' . date('Y') . ' Investor.ID Editorial Newsroom</p>';
            $body .= '</div>';

            $email->setMessage($body);
            $sent = $email->send();
            if (!$sent) {
                log_message('error', 'Gagal mengirim email reset via Brevo: ' . $email->printDebugger(['headers', 'subject', 'body']));
            }
            return $sent;
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mengirim email reset: ' . $e->getMessage());
            return false;
        }
    }
}
