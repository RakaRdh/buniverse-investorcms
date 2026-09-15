<?php

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\ActivityLogModel;

class User extends BaseController
{
    protected AdminModel $adminModel;
    protected ActivityLogModel $activityLogModel;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->activityLogModel = new ActivityLogModel();
    }

    public function index()
    {
        $filters = [
            'q'      => trim($this->request->getGet('q') ?? ''),
            'status' => trim($this->request->getGet('status') ?? 'all'),
            'role'   => trim($this->request->getGet('role') ?? 'all'),
        ];

        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 30;
        $totalUsers = $this->adminModel->countAdminsWithActivity($filters);
        $totalPages = max(1, (int)ceil($totalUsers / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $users = $this->adminModel->getAdminsWithActivity($filters, $perPage, $offset);

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalUsers,
            'totalPages' => $totalPages,
            'from'       => $totalUsers > 0 ? $offset + 1 : 0,
            'to'         => min($offset + count($users), $totalUsers),
        ];

        $permFile = WRITEPATH . 'data/json/permissions.json';
        $perms = [];
        if (file_exists($permFile)) {
            $perms = json_decode(file_get_contents($permFile), true) ?: [];
        }

        return view('user/index', [
            'title'          => 'User Management & Newsroom Staff',
            'breadcrumb'     => 'Management / User',
            'users'          => $users,
            'filters'        => $filters,
            'pagination'     => $pagination,
            'permissionsMap' => $perms,
        ]);
    }

    public function save()
    {
        $sessionRole = session()->get('user_role') ?? 'Admin';
        $name = trim($this->request->getPost('name') ?? '');
        $email = trim($this->request->getPost('email') ?? '');
        $usernameInput = trim($this->request->getPost('username') ?? '');
        $role = $this->request->getPost('role') ?? 'Author';

        // Only Master can assign Master role
        if (strcasecmp($role, 'Master') === 0 && strcasecmp($sessionRole, 'Master') !== 0) {
            $role = 'Admin';
        }

        if (!empty($name) && !empty($email)) {
            if (!empty($usernameInput)) {
                // Sanitize input username (lowercase, alphanumeric, underscores, dashes, dots)
                $username = strtolower(preg_replace('/[^a-zA-Z0-9_.-]/', '', $usernameInput));
            } else {
                $username = strtolower(explode(' ', $name)[0]) . '_' . rand(10, 99);
            }

            // Ensure username uniqueness in useradministrator (username)
            $existing = $this->adminModel->where('username', $username)->first();
            if ($existing) {
                $username .= '_' . rand(10, 99);
            }

            $passwordInput = trim($this->request->getPost('password') ?? '');
            $plainPassword = !empty($passwordInput) ? $passwordInput : 'password123';
            $salt = AdminModel::generateSalt($username, $plainPassword);
            $hash = AdminModel::encodePassword($plainPassword, $salt);

            $aliasInput = trim($this->request->getPost('alias') ?? '');
            $userAlias = !empty($aliasInput) ? $aliasInput : strtoupper(substr($name, 0, 3));

            $this->adminModel->insert([
                'name'      => $name,
                'username'  => $username,
                'email'     => $email,
                'alias'     => $userAlias,
                'slug'      => url_title($name, '-', true),
                'password'  => $hash,
                'salt'      => $salt,
                'roleName'  => $role,
                'active'    => '1',
                'lastLogin' => date('Y-m-d H:i:s'),
            ]);

            $sessionUser = session()->get('user_id') ?: 1;
            $this->activityLogModel->logAction($sessionUser, 'USER', 'CREATE', "Added newsroom staff: {$name} ({$username}) as {$role}");
        }

        return redirect()->to('/user')->with('success', 'User staff account created in database.');
    }

    public function delete($id)
    {
        $sessionUser = (int)(session()->get('user_id') ?: 9);
        $sessionRole = session()->get('user_role') ?? 'Admin';
        $targetId = (int)$id;

        // Prevent self-deletion
        if ($targetId === $sessionUser) {
            return redirect()->to('/user')->with('error', 'Cannot delete currently active logged in user account.');
        }

        $targetUser = $this->adminModel->find($targetId);
        if (!$targetUser) {
            return redirect()->to('/user')->with('error', 'User account not found.');
        }

        $targetRole = $targetUser['roleName'] ?? $targetUser['attr_role_code'] ?? '';

        // Role hierarchy: Only Master can delete Master or Admin accounts
        if (strcasecmp($sessionRole, 'Master') !== 0) {
            if (in_array(strtolower($targetRole), ['master', 'admin'])) {
                return redirect()->to('/user')->with('error', 'Unauthorized: Only Master can delete Administrator or Master accounts.');
            }
        }

        $targetName = $targetUser['name'] ?? $targetUser['attr_display_name'] ?? "User #{$targetId}";
        $this->activityLogModel->logAction($sessionUser, 'USER', 'DELETE', "Deleted user ID: {$targetId} ({$targetName})");
        $this->adminModel->delete($targetId);

        return redirect()->to('/user')->with('success', 'User removed.');
    }

    /**
     * Get permission configuration for a specific user
     */
    public function getPermissions($id)
    {
        $sessionRole = strtolower(session()->get('user_role') ?? 'admin');
        if (!in_array($sessionRole, ['master', 'admin', 'administrator'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $permFile = WRITEPATH . 'data/json/permissions.json';
        $perms = [];
        if (file_exists($permFile)) {
            $perms = json_decode(file_get_contents($permFile), true) ?: [];
        }

        $userId = (string)$id;
        $user = $this->adminModel->find((int)$id);
        if (!$user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found'])->setStatusCode(404);
        }

        $userRole = strtolower($user['roleName'] ?? $user['attr_role_code'] ?? 'author');
        $defaultRolePerms = $perms['roles'][$userRole] ?? [];
        $userCustomPerms = $perms['users'][$userId] ?? $defaultRolePerms;

        return $this->response->setJSON([
            'status'      => 'success',
            'user_id'     => (int)$id,
            'user_name'   => $user['name'] ?? $user['attr_display_name'] ?? '',
            'user_role'   => $user['roleName'] ?? $user['attr_role_code'] ?? '',
            'permissions' => $userCustomPerms
        ]);
    }

    /**
     * Save customized page permissions for a user
     */
    public function savePermissions()
    {
        $sessionRole = strtolower(session()->get('user_role') ?? 'admin');
        if (!in_array($sessionRole, ['master', 'admin', 'administrator'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $userId = $this->request->getPost('user_id');
        $pages = $this->request->getPost('pages') ?: []; // array of page keys

        if (empty($userId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid user ID'])->setStatusCode(400);
        }

        $permFile = WRITEPATH . 'data/json/permissions.json';
        $perms = ['roles' => [], 'users' => []];
        if (file_exists($permFile)) {
            $perms = json_decode(file_get_contents($permFile), true) ?: ['roles' => [], 'users' => []];
        }

        $pagePerms = [];
        if (is_array($pages)) {
            foreach ($pages as $p) {
                $pagePerms[trim($p)] = true;
            }
        }

        $perms['users'][(string)$userId] = $pagePerms;
        file_put_contents($permFile, json_encode($perms, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $sessionUser = (int)(session()->get('user_id') ?: 9);
        $this->activityLogModel->logAction($sessionUser, 'USER', 'UPDATE', "Updated page permissions for User ID: {$userId}");

        return $this->response->setJSON(['status' => 'success', 'message' => 'Permissions updated successfully.']);
    }

    /**
     * User Profile Page (Read-only for details, editable alias, and security tab)
     */
    public function profile()
    {
        $userId = session()->get('user_id') ?: 1;
        $user = $this->adminModel->find($userId);

        if (!$user) {
            return redirect()->to('/dashboard')->with('error', 'Akun pengguna tidak ditemukan.');
        }

        // Kalkulasi sisa masa berlaku kata sandi (90 hari)
        $isPasswordExpired = false;
        $daysRemaining = 90;
        $expiredDate = null;

        if (!empty($user['passwordExpired'])) {
            $expiredDate = $user['passwordExpired'];
            $nowTs = time();
            $expiredTs = strtotime($user['passwordExpired']);
            $diffSeconds = $expiredTs - $nowTs;
            $daysRemaining = (int)floor($diffSeconds / (60 * 60 * 24));
            if ($daysRemaining < 0) {
                $isPasswordExpired = true;
            }
        }

        $activeTab = $this->request->getGet('tab') ?? 'profile';
        if (!in_array($activeTab, ['profile', 'security'])) {
            $activeTab = 'profile';
        }

        return view('user/profile', [
            'title'             => 'User Profile',
            'breadcrumb'        => 'Account / Profile',
            'user'              => $user,
            'activeTab'         => $activeTab,
            'isPasswordExpired' => $isPasswordExpired,
            'daysRemaining'     => $daysRemaining,
            'expiredDate'       => $expiredDate,
        ]);
    }

    /**
     * Update user profile alias
     */
    public function updateProfile()
    {
        $userId = session()->get('user_id') ?: 1;
        $user = $this->adminModel->find($userId);

        if (!$user) {
            return redirect()->to('/dashboard')->with('error', 'User account not found.');
        }

        $alias = trim($this->request->getPost('alias') ?? '');

        $this->adminModel->update($userId, [
            'alias' => $alias,
        ]);

        $this->activityLogModel->logAction(
            $userId,
            'USER',
            'UPDATE_PROFILE',
            "Updated alias to '{$alias}' for user '{$user['username']}'"
        );

        return redirect()->to('/user/profile?tab=profile')->with('success', 'Profile alias updated successfully.');
    }

    /**
     * Ubah kata sandi mandiri dari halaman profil pengguna (Tab Keamanan)
     * Memvalidasi kata sandi lama, mencegah reuse kata sandi lama, mengecek kekuatan password,
     * lalu menyimpan hash SHA-512 + 128 chars deterministik salt baru,
     * dan menghancurkan sesi untuk memaksa re-login demi keamanan (standar legacy).
     */
    public function changePasswordProfile()
    {
        $userId = session()->get('user_id') ?: 1;
        $user = $this->adminModel->find($userId);

        if (!$user) {
            return redirect()->to('/login');
        }

        $currentPassword = (string)$this->request->getPost('current_password');
        $newPassword     = (string)$this->request->getPost('new_password');
        $confirmPassword = (string)$this->request->getPost('confirm_password');

        if (empty($currentPassword) || empty($newPassword)) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Kata sandi saat ini dan kata sandi baru wajib diisi.');
        }

        if ($newPassword === $currentPassword) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Kata sandi baru tidak boleh sama persis dengan kata sandi saat ini.');
        }

        $strengthErrors = \App\Models\AdminModel::validatePasswordStrength($newPassword);
        if (!empty($strengthErrors)) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Kombinasi kata sandi baru belum memenuhi standar: ' . implode(' ', $strengthErrors));
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Konfirmasi kata sandi baru tidak cocok.');
        }

        // Verifikasi kata sandi saat ini
        $salt = $user['salt'] ?? '';
        $storedHash = $user['password'] ?? '';
        $username = $user['username'] ?? '';
        $isValidCurrent = false;

        if (\App\Models\AdminModel::verifyPassword($currentPassword, $salt, $storedHash, $username)) {
            $isValidCurrent = true;
        } elseif (!empty($salt) && \App\Models\AdminModel::decodePassword($storedHash, $salt) === $currentPassword) {
            $isValidCurrent = true;
        } elseif (password_verify($currentPassword, $storedHash)) {
            $isValidCurrent = true;
        } elseif (strcasecmp(md5($currentPassword), $storedHash) === 0) {
            $isValidCurrent = true;
        }

        if (!$isValidCurrent) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Kata sandi saat ini tidak sesuai.');
        }

        // Update password baru dengan skema legacy SHA-512 salt deterministik + pepper
        $newSalt = \App\Models\AdminModel::generateSalt($username, $newPassword);
        $newHash = \App\Models\AdminModel::encodePassword($newPassword, $newSalt);
        $newExpiry = date('Y-m-d H:i:s', strtotime('+90 days'));

        $this->adminModel->update($userId, [
            'password'        => $newHash,
            'salt'            => $newSalt,
            'passwordExpired' => $newExpiry,
        ]);

        $this->activityLogModel->logAction(
            $userId,
            'USER',
            'CHANGE_PASSWORD',
            "User {$user['username']} changed password via profile page"
        );

        // Standar keamanan legacy: destroy session dan paksa user re-login dengan password baru
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Kata sandi berhasil diperbarui! Demi keamanan, silakan masuk kembali menggunakan kata sandi baru Anda.');
    }

    /**
     * Minta link konfirmasi perubahan password via email resmi staf
     */
    public function requestResetEmail()
    {
        $userId = session()->get('user_id') ?: 1;
        $user = $this->adminModel->find($userId);

        if (!$user || empty($user['email'])) {
            return redirect()->to('/user/profile?tab=security')->with('error', 'Alamat email pengguna tidak ditemukan.');
        }

        $resetToken = (string)random_int(100000, 999999);
        $authExpired = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $this->adminModel->update($userId, [
            'authCode'    => (int)$resetToken,
            'authExpired' => $authExpired,
        ]);

        try {
            $email = \Config\Services::email();
            $email->setTo($user['email']);
            $email->setSubject('Konfirmasi Perubahan Kata Sandi - CMS Investor');
            $resetUrl = site_url('auth/reset-password?token=' . urlencode($resetToken) . '&user=' . urlencode($user['username']));
            
            $body = '<div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 28px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff; color: #1e293b;">';
            $body .= '<div style="margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">';
            $body .= '<h2 style="color: #1A365D; margin: 0; font-size: 20px; font-weight: 700;">CMS Investor.id</h2>';
            $body .= '<p style="color: #64748B; font-size: 12px; margin: 4px 0 0 0;">Konfirmasi Perubahan Kata Sandi</p>';
            $body .= '</div>';
            $body .= '<p style="font-size: 14px; line-height: 1.5;">Halo, <strong>' . esc($user['name'] ?: $user['username']) . '</strong>,</p>';
            $body .= '<p style="font-size: 14px; line-height: 1.5; color: #334155;">Anda telah meminta tautan konfirmasi untuk memperbarui kata sandi akun editorial Anda. Silakan klik tombol di bawah ini:</p>';
            $body .= '<div style="text-align: center; margin: 28px 0;">';
            $body .= '<a href="' . $resetUrl . '" style="display: inline-block; font-size: 14px; font-weight: 700; color: #ffffff; background-color: #0077CC; padding: 14px 32px; border-radius: 8px; text-decoration: none; box-shadow: 0 3px 6px rgba(0,119,204,0.25);">Konfirmasi & Set Sandi Baru</a>';
            $body .= '</div>';
            $body .= '<div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 12px; color: #475569;">';
            $body .= '<strong style="color: #1e293b; display: block; margin-bottom: 4px;">Tombol tidak dapat diklik?</strong>';
            $body .= 'Salin tautan ini ke peramban Anda:<br><a href="' . $resetUrl . '" style="color: #0077CC; word-break: break-all; text-decoration: underline;">' . $resetUrl . '</a><br><br>';
            $body .= 'Atau kode token alternatif: <strong style="font-family: monospace; font-size: 14px; color: #0077CC; letter-spacing: 2px;">' . $resetToken . '</strong>';
            $body .= '</div>';
            $body .= '<p style="color: #ef4444; font-size: 12px; line-height: 1.5; font-weight: 600;">Tautan ini hanya berlaku selama 15 menit.</p>';
            $body .= '<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0 16px 0;">';
            $body .= '<p style="color: #94a3b8; font-size: 11px; margin: 0; text-align: center;">&copy; ' . date('Y') . ' Investor.ID Editorial Newsroom</p>';
            $body .= '</div>';

            $email->setMessage($body);
            $email->send();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal kirim email reset dari profile: ' . $e->getMessage());
        }

        return redirect()->to('/user/profile?tab=security')->with('success', 'Tautan konfirmasi perubahan kata sandi telah dikirimkan ke email resmi Anda (' . $user['email'] . '). Silakan periksa inbox.');
    }

    /**
     * POST /user/toggle-status/{id}
     * Block/Unblock user account (Flip active 0 <-> 1)
     * Replika legacy: Administrator::blockUser() & Administrator::unblockUser()
     * Tabel: useradministrator (W)
     */
    public function toggleStatus(int $id)
    {
        $sessionUser = (int)(session()->get('user_id') ?: 0);
        $sessionRole = strtolower(session()->get('user_role') ?? 'admin');

        if (!in_array($sessionRole, ['master', 'admin', 'administrator'])) {
            return redirect()->to('/user')->with('error', 'Akses ditolak: Hanya Administrator yang boleh mengubah status user.');
        }

        if ($id === $sessionUser) {
            return redirect()->to('/user')->with('error', 'Tidak dapat menonaktifkan akun sendiri yang sedang aktif digunakan.');
        }

        $user = $this->adminModel->find($id);
        if (!$user) {
            return redirect()->to('/user')->with('error', 'Akun pengguna tidak ditemukan.');
        }

        $currentActive = (int)($user['active'] ?? 1);
        $newActive = $currentActive === 1 ? '0' : '1';

        $this->adminModel->update($id, [
            'active' => $newActive
        ]);

        $statusLabel = $newActive === '1' ? 'diaktifkan kembali (unblocked)' : 'dinonaktifkan (blocked)';
        $this->activityLogModel->logAction(
            $sessionUser,
            'USER',
            $newActive === '1' ? 'UNBLOCK' : 'BLOCK',
            "User {$user['username']} ID #{$id} {$statusLabel}"
        );

        return redirect()->to('/user')->with('success', "Akun '{$user['username']}' berhasil {$statusLabel}.");
    }

    /**
     * GET /api/user/check-email?email=xxx
     * Replika legacy: checkemail.php (Ajax check email availability)
     * Tabel: useradministrator (R)
     */
    public function checkEmail()
    {
        $email = trim($this->request->getGet('email') ?? '');
        $excludeId = (int)($this->request->getGet('exclude_id') ?? 0);

        if (empty($email)) {
            return $this->response->setJSON(['exists' => false]);
        }

        $builder = $this->adminModel->where('email', $email);
        if ($excludeId > 0) {
            $builder->where('userID !=', $excludeId);
        }
        $exists = $builder->countAllResults() > 0;

        return $this->response->setJSON([
            'exists' => $exists,
            'email'  => $email
        ]);
    }
}
