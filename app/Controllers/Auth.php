<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Auth extends BaseController
{
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

        if ($this->request->getMethod() === 'POST') {
            $email = trim($this->request->getPost('email') ?? '');
            $password = trim($this->request->getPost('password') ?? '');

            if (empty($email) || empty($password)) {
                return redirect()->to('/login')->with('error', 'Silakan masukkan email kantor dan kata sandi.');
            }

            $user = DataRepository::authenticate($email, $password);

            if ($user) {
                // Compute user initials
                $nameParts = explode(' ', $user['name']);
                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

                $session->set([
                    'user_id' => $user['id'],
                    'user_name' => $user['name'],
                    'user_email' => $user['email'],
                    'user_role' => $user['role'],
                    'user_username' => $user['username'],
                    'user_initials' => $initials,
                ]);

                return redirect()->to('/dashboard')->with('success', 'Selamat datang kembali, ' . esc($user['name']) . '!');
            }

            return redirect()->to('/login')->with('error', 'Kredensial tidak valid. Silakan periksa email atau kata sandi Anda.');
        }

        $users = DataRepository::get('users');
        return view('auth/login', ['demoUsers' => $users]);
    }
}
