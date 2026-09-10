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
        $perPage = 15;
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
            $salt = AdminModel::generateSalt(128);
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
     * User Profile Page (Read-only for details, editable alias only)
     */
    public function profile()
    {
        $userId = session()->get('user_id') ?: 1;
        $user = $this->adminModel->find($userId);

        if (!$user) {
            return redirect()->to('/dashboard')->with('error', 'User account not found.');
        }

        return view('user/profile', [
            'title'      => 'User Profile',
            'breadcrumb' => 'Account / Profile',
            'user'       => $user,
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

        return redirect()->to('/user/profile')->with('success', 'Profile alias updated successfully.');
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
