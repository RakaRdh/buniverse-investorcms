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
        return redirect()->to('/login')->with('success', 'You have been logged out from Investor.id CMS workspace.');
    }

    public function login()
    {
        $session = session();

        // If already logged in, redirect directly to dashboard
        if ($session->has('user_id')) {
            return redirect()->to('/dashboard');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            $username = trim($this->request->getPost('username') ?? $this->request->getPost('email') ?? '');
            $password = trim($this->request->getPost('password') ?? '');

            if (empty($username) || empty($password)) {
                return redirect()->to('/login')->with('error', 'Silakan masukkan username dan kata sandi.');
            }

            $user = $this->adminModel->authenticate($username, $password);

            if ($user) {
                // Compute user initials
                $nameParts = explode(' ', $user['attr_display_name']);
                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

                $session->set([
                    'user_id'       => $user['ref_entity_principal_id'],
                    'user_name'     => $user['attr_display_name'],
                    'user_email'    => $user['attr_contact_val'],
                    'user_role'     => $user['attr_role_code'],
                    'user_username' => $user['attr_identifier'],
                    'user_initials' => $initials,
                ]);

                return redirect()->to('/dashboard')->with('success', 'Selamat datang kembali, ' . esc($user['attr_display_name']) . '!');
            }

            return redirect()->to('/login')->with('error', 'Kredensial tidak valid. Silakan periksa username atau kata sandi Anda.');
        }

        // Demo users for quick selection in development login screen
        $demoUsers = $this->adminModel->select('
                ref_entity_principal_id AS id,
                attr_display_name AS name,
                attr_identifier AS username,
                attr_contact_val AS email,
                attr_role_code AS role
            ')
            ->findAll();

        return view('auth/login', ['demoUsers' => $demoUsers]);
    }
}
