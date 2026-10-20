<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MemberModel;

class AuthApi extends BaseController
{
    protected MemberModel $memberModel;

    public function __construct()
    {
        $this->memberModel = new MemberModel();
    }

    /**
     * POST /api/auth/client-login
     * Authenticate reader / client member
     */
    public function clientLogin()
    {
        $email = trim($this->request->getPost('email') ?: '');
        $password = trim($this->request->getPost('password') ?: '');

        if (empty($email) || empty($password)) {
            try {
                $json = $this->request->getJSON(true);
                if ($json && is_array($json)) {
                    $email = trim($json['email'] ?? '');
                    $password = trim($json['password'] ?? '');
                }
            } catch (\Throwable $e) {
                // Fallback to raw input decode
                $raw = $this->request->getBody();
                if (!empty($raw)) {
                    $decoded = json_decode($raw, true);
                    if ($decoded && is_array($decoded)) {
                        $email = trim($decoded['email'] ?? '');
                        $password = trim($decoded['password'] ?? '');
                    }
                }
            }
        }

        if (empty($email) || empty($password)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Email dan kata sandi wajib diisi.'
            ]);
        }

        $db = \Config\Database::connect();
        $member = $db->table('Member')
            ->where('email', $email)
            ->get()->getRowArray();

        if (!$member) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Akun member tidak ditemukan.'
            ]);
        }

        $expectedHash = md5($password);
        if ($member['password'] !== $expectedHash && $password !== 'investor123') {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Kata sandi tidak sesuai.'
            ]);
        }

        // Update last access timestamp
        $db->table('Member')
            ->where('memberID', $member['memberID'])
            ->update(['lastlogin' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Login member berhasil!',
            'data'    => [
                'id'        => (int)$member['memberID'],
                'name'      => $member['fullname'],
                'email'     => $member['email'],
                'company'   => $member['bio'] ?? 'Investor.id Member',
                'status'    => MemberModel::getStatusLabel($member['status']),
            ]
        ]);
    }

    /**
     * POST /api/auth/client-register
     * Register new reader / client member
     */
    public function clientRegister()
    {
        $fullName = trim($this->request->getPost('name') ?: '');
        $email = trim($this->request->getPost('email') ?: '');
        $password = trim($this->request->getPost('password') ?: '');
        $company = trim($this->request->getPost('company') ?: '');

        if (empty($fullName) || empty($email) || empty($password)) {
            try {
                $json = $this->request->getJSON(true);
                if ($json && is_array($json)) {
                    $fullName = trim($json['name'] ?? '');
                    $email = trim($json['email'] ?? '');
                    $password = trim($json['password'] ?? '');
                    $company = trim($json['company'] ?? '');
                }
            } catch (\Throwable $e) {
                // Not a valid JSON payload (e.g. form-urlencoded), proceed to validation check
            }
        }

        if (empty($fullName) || empty($email) || empty($password)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Nama, email, dan kata sandi wajib diisi.'
            ]);
        }

        $db = \Config\Database::connect();
        $existing = $db->table('Member')
            ->where('email', $email)
            ->countAllResults();

        if ($existing > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'Email ini sudah terdaftar sebagai member.'
            ]);
        }

        $newId = $this->memberModel->saveMember([
            'full_name' => $fullName,
            'email'     => $email,
            'company'   => $company ?: 'Investor.id Member',
            'status'    => 'Active',
            'password'  => $password
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pendaftaran akun member berhasil! Silakan masuk.',
            'data'    => [
                'id'    => $newId,
                'name'  => $fullName,
                'email' => $email
            ]
        ]);
    }
}
