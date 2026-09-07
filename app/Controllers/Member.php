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
        $data = [
            'full_name' => $this->request->getPost('full_name'),
            'email'     => $this->request->getPost('email'),
            'company'   => $this->request->getPost('company'),
            'status'    => $this->request->getPost('status') ?? 'Active',
        ];

        $memberId = !empty($id) ? (int)$id : null;
        $this->memberModel->saveMember($data, $memberId);

        $msg = !empty($id) ? 'Data member berhasil diperbarui.' : 'Member baru berhasil ditambahkan.';
        return redirect()->to('/member')->with('success', $msg);
    }

    public function delete($id)
    {
        $this->memberModel->deleteMember((int)$id);
        return redirect()->to('/member')->with('success', 'Member berhasil dihapus dari sistem.');
    }
}
