<?php

namespace App\Controllers;

use App\Models\MemberModel;

class Member extends BaseController
{
    protected MemberModel $memberModel;

    public function __construct()
    {
        $this->memberModel = new MemberModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('q');
        $status = $this->request->getGet('status') ?? 'all';
        $page = (int)($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 10;

        $filters = [
            'q'      => $keyword,
            'status' => $status,
        ];

        $result = $this->memberModel->getMembers($filters, $page, $perPage);
        $stats = $this->memberModel->getMemberStats();

        return view('member/index', [
            'title'      => 'Client & Member Management',
            'breadcrumb' => 'Management / Member',
            'members'    => $result['data'],
            'stats'      => $stats,
            'filters'    => $filters,
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'totalItems' => $result['totalItems'],
                'totalPages' => $result['totalPages'],
                'from'       => $result['totalItems'] > 0 ? $result['offset'] + 1 : 0,
                'to'         => min($result['offset'] + $perPage, $result['totalItems']),
            ]
        ]);
    }

    public function save()
    {
        $id = $this->request->getPost('id');
        $fullName = trim((string)($this->request->getPost('full_name') ?? ''));
        $email = trim((string)($this->request->getPost('email') ?? ''));

        if (empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to('/member')->with('error', 'Nama lengkap dan alamat email yang valid wajib diisi.');
        }

        $data = [
            'full_name' => $fullName,
            'email'     => $email,
            'company'   => trim((string)($this->request->getPost('company') ?? '')),
            'status'    => $this->request->getPost('status') ?? 'Active',
        ];

        $memberId = !empty($id) ? (int)$id : null;
        $this->memberModel->saveMember($data, $memberId);

        $msg = !empty($id) ? 'Data member berhasil diperbarui.' : 'Member baru berhasil ditambahkan.';
        return redirect()->to('/member')->with('success', $msg);
    }

    public function delete($id)
    {
        $sessionUser = (int)(session()->get('user_id') ?: 1);
        $targetId = (int)$id;

        try {
            $activityLog = new \App\Models\ActivityLogModel();
            $activityLog->logAction($sessionUser, 'MEMBER', 'DELETE', "Deleted member ID: {$targetId}");
        } catch (\Throwable $e) {}

        $this->memberModel->deleteMember($targetId);
        return redirect()->to('/member')->with('success', 'Member berhasil dihapus dari sistem.');
    }
}
