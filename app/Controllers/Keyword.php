<?php

namespace App\Controllers;

use App\Models\KeywordModel;

class Keyword extends BaseController
{
    protected KeywordModel $keywordModel;

    public function __construct()
    {
        $this->keywordModel = new KeywordModel();
    }

    /**
     * GET /keyword
     * Menampilkan daftar master keyword dengan jumlah artikel terkait & pencarian
     */
    public function index()
    {
        $q = trim($this->request->getGet('q') ?? '');
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 25;

        $filters = ['q' => $q];
        $result = $this->keywordModel->getKeywordsWithCount($filters, $page, $perPage);

        $totalItems = $result['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'from'       => $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'         => min($page * $perPage, $totalItems),
        ];

        return view('keyword/index', [
            'title'      => 'Keyword Management',
            'breadcrumb' => 'Post / Keyword',
            'keywords'   => $result['data'],
            'pagination' => $pagination,
            'q'          => $q,
        ]);
    }

    /**
     * POST /keyword/save
     * Create or Update keyword
     */
    public function save()
    {
        $id = (int)$this->request->getPost('id');
        $rawKeyword = trim($this->request->getPost('keyword') ?? '');

        if (empty($rawKeyword)) {
            return redirect()->to('/keyword')->with('error', 'Nama keyword tidak boleh kosong.');
        }

        $slug = trim($this->request->getPost('slug') ?? '');
        if (empty($slug)) {
            $slug = url_title($rawKeyword, '-', true);
        }

        // Cek duplikasi keyword selain ID yang sedang diedit
        $check = $this->keywordModel->where('keyword', $rawKeyword);
        if ($id > 0) {
            $check->where('id !=', $id);
        }
        if ($check->first()) {
            return redirect()->to('/keyword')->with('error', "Keyword '{$rawKeyword}' sudah terdaftar.");
        }

        if ($id > 0) {
            $this->keywordModel->update($id, [
                'keyword' => $rawKeyword,
                'slug'    => $slug,
            ]);
            $msg = "Keyword '{$rawKeyword}' berhasil diperbarui.";
        } else {
            $this->keywordModel->insert([
                'keyword' => $rawKeyword,
                'slug'    => $slug,
            ]);
            $msg = "Keyword baru '{$rawKeyword}' berhasil ditambahkan.";
        }

        return redirect()->to('/keyword')->with('success', $msg);
    }

    /**
     * GET /keyword/delete/(:num)
     * Menghapus keyword dan relasinya di artikel
     */
    public function delete(int $id)
    {
        $userRole = strtolower(session('user_role') ?? '');
        if (!in_array($userRole, ['master', 'administrator', 'redpel'])) {
            return redirect()->to('/keyword')->with('error', 'Akses ditolak: Hanya Redpel dan Admin yang boleh menghapus master keyword.');
        }

        $kw = $this->keywordModel->find($id);
        if (!$kw) {
            return redirect()->to('/keyword')->with('error', 'Keyword tidak ditemukan.');
        }

        $this->keywordModel->deleteKeyword($id);

        return redirect()->to('/keyword')->with('success', "Keyword '{$kw['keyword']}' berhasil dihapus.");
    }
}
