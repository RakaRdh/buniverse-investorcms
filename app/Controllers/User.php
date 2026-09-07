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
        $users = $this->adminModel->getAdminsWithActivity();

        return view('user/index', [
            'title'      => 'User Management & Newsroom Staff',
            'breadcrumb' => 'Management / User',
            'users'      => $users
        ]);
    }

    public function save()
    {
        $sessionRole = session()->get('user_role') ?? 'Admin';
        $name = trim($this->request->getPost('name') ?? '');
        $email = trim($this->request->getPost('email') ?? '');
        $role = $this->request->getPost('role') ?? 'Author';

        // Only Master can assign Master role
        if (strcasecmp($role, 'Master') === 0 && strcasecmp($sessionRole, 'Master') !== 0) {
            $role = 'Admin';
        }

        if (!empty($name) && !empty($email)) {
            $username = strtolower(explode(' ', $name)[0]) . '_' . rand(10, 99);

            $salt = AdminModel::generateSalt(16);
            $hash = AdminModel::encodePassword('password123', $salt);

            $this->adminModel->insert([
                'attr_display_name'    => $name,
                'attr_identifier'      => $username,
                'attr_contact_val'     => $email,
                'attr_alt_label'       => $email,
                'attr_slug_token'      => url_title($name, '-', true),
                'attr_credential_hash' => $hash,
                'attr_credential_salt' => $salt,
                'attr_role_code'       => $role,
                'attr_is_active'       => 1,
                'attr_last_access_at'  => date('Y-m-d H:i:s'),
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

        $targetRole = $targetUser['attr_role_code'] ?? '';

        // Role hierarchy: Only Master can delete Master or Admin accounts
        if (strcasecmp($sessionRole, 'Master') !== 0) {
            if (in_array(strtolower($targetRole), ['master', 'admin'])) {
                return redirect()->to('/user')->with('error', 'Unauthorized: Only Master can delete Administrator or Master accounts.');
            }
        }

        $this->activityLogModel->logAction($sessionUser, 'USER', 'DELETE', "Deleted user ID: {$targetId} ({$targetUser['attr_display_name']})");
        $this->adminModel->delete($targetId);

        return redirect()->to('/user')->with('success', 'User removed.');
    }
}
