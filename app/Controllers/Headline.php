<?php

namespace App\Controllers;

use App\Models\HeadlineModel;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class Headline extends BaseController
{
    protected HeadlineModel $headlineModel;
    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->headlineModel = new HeadlineModel();
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->getKanalTree();

        // Selected target category (defaults to 'headline')
        $targetCategory = $this->request->getGet('target_category') ?: 'headline';
        if (strtolower($targetCategory) === 'home') {
            $targetCategory = 'headline';
        }
        $headlines = $this->headlineModel->getActiveHeadlines($targetCategory);

        // Filtering parameters for available posts
        $keyword = $this->request->getGet('q');
        $categoryFilter = $this->request->getGet('category');
        $page = (int)($this->request->getGet('page') ?? 1);

        $filters = [
            'status'   => 'Publish',
            'q'        => $keyword,
            'kanal'    => $categoryFilter,
            'order_by' => 'articleDate',
        ];

        $perPage = 25;
        $availableResult = $this->articleModel->getArticlesWithRelations($filters, $page, $perPage);

        $totalItems = $availableResult['total'] ?? count($availableResult['data']);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'from'       => $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'         => min($page * $perPage, $totalItems),
        ];

        $activeHeadlineMap = [];
        foreach ($headlines as $hl) {
            $activeHeadlineMap[(int)$hl['id']] = (int)$hl['headline_position'];
        }

        return view('headline/index', [
            'title'             => 'Headline Management',
            'breadcrumb'        => 'Headline / Placements',
            'availablePosts'    => $availableResult['data'],
            'pagination'        => $pagination,
            'headlines'         => $headlines,
            'activeHeadlineMap' => $activeHeadlineMap,
            'targetCategory'    => $targetCategory,
            'categories'        => $categories,
            'filters'           => [
                'q'        => $keyword ?? '',
                'category' => $categoryFilter ?? 'all'
            ]
        ]);
    }

    public function add()
    {
        $articleId = (int)$this->request->getPost('article_id');
        $targetCategory = $this->request->getPost('target_category') ?: 'headline';
        if (strtolower($targetCategory) === 'home') {
            $targetCategory = 'headline';
        }

        // Server-side validation: Verify article exists and is Published
        $article = $this->articleModel->find($articleId);
        if (!$article) {
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('error', "Artikel tidak ditemukan.");
        }

        if (!in_array(strtolower($article['status'] ?? ''), ['publish', 'published'])) {
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('error', "Gagal: Hanya artikel berstatus 'Publish' yang dapat dijadikan headline.");
        }

        // Future Publish At protection: Do not allow articles whose publish time has not arrived yet
        if (!empty($article['articleDate']) && strtotime($article['articleDate']) > time()) {
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('error', "Gagal: Artikel belum mencapai jadwal tayang (Publish At di masa mendatang).");
        }

        $existing = $this->headlineModel
            ->where('articleID', $articleId)
            ->where('category', $targetCategory)
            ->first();

        // 1. If already active in this category, reject with clear informative message
        if ($existing && (int)$existing['status'] === 1) {
            $pos = $existing['num'] ?? 1;
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))
                ->with('error', "Artikel sudah terdaftar sebagai headline aktif di kategori '{$targetCategory}' (pada posisi #{$pos}).");
        }

        // 2. Enforce max 5 articles quota per category (Legacy parity)
        $activeCount = $this->headlineModel
            ->where('category', $targetCategory)
            ->where('status', 1)
            ->countAllResults();

        if ($activeCount >= 5) {
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))
                ->with('error', "Batas maksimal tercapai: Kategori '{$targetCategory}' sudah memiliki 5 artikel headline. Silakan hapus atau gantikan salah satu artikel terlebih dahulu.");
        }

        $maxPos = $this->headlineModel
            ->where('category', $targetCategory)
            ->where('status', 1)
            ->selectMax('num')
            ->first()['num'] ?? 0;

        $currentUser = session('user_username') ?: (session('username') ?: (session('user_name') ?: 'admin'));

        if (!$existing) {
            $newPos = $maxPos + 1;
            $this->headlineModel->insert([
                'category'   => $targetCategory,
                'articleID'  => $articleId,
                'num'        => $newPos,
                'timestamp'  => date('Y-m-d H:i:s'),
                'status'     => 1,
                'set_by'     => $currentUser,
            ]);
            $this->headlineModel->logHistory($articleId, $targetCategory, 'ADD', $newPos, $currentUser, "Menambahkan artikel ke headline posisi #{$newPos}");
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel berhasil ditambahkan ke antrean headline {$targetCategory}.");
        } else if ((int)$existing['status'] === 0) {
            // Re-activate previously removed headline
            $newPos = $maxPos + 1;
            $this->headlineModel->update($existing['id'], [
                'num'       => $newPos,
                'timestamp' => date('Y-m-d H:i:s'),
                'status'    => 1,
                'set_by'    => $currentUser,
            ]);
            $this->headlineModel->logHistory($articleId, $targetCategory, 'ADD', $newPos, $currentUser, "Mengaktifkan kembali artikel ke headline posisi #{$newPos}");
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel berhasil diaktifkan kembali di antrean headline {$targetCategory}.");
        }

        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('info', "Artikel sudah terdaftar di antrean headline {$targetCategory}.");
    }

    public function remove($id)
    {
        $targetCategory = $this->request->getGet('target_category') ?: 'headline';
        if (strtolower($targetCategory) === 'home') {
            $targetCategory = 'headline';
        }

        $currentUser = session('user_username') ?: (session('username') ?: (session('user_name') ?: 'admin'));

        // Ambil info posisi sebelum dinonaktifkan
        $currentHl = $this->headlineModel
            ->where('articleID', (int)$id)
            ->where('category', $targetCategory)
            ->first();
        $prevPos = $currentHl['num'] ?? null;

        // Production convention: set status = 0 rather than hard deleting to preserve audit log
        $this->headlineModel
            ->where('articleID', (int)$id)
            ->where('category', $targetCategory)
            ->set([
                'status'    => 0,
                'set_by'    => $currentUser,
                'timestamp' => date('Y-m-d H:i:s'),
            ])
            ->update();

        $this->headlineModel->logHistory((int)$id, $targetCategory, 'REMOVE', $prevPos, $currentUser, "Menghapus artikel dari antrean headline (sebelumnya posisi #{$prevPos})");

        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel dinonaktifkan dari antrean headline {$targetCategory}.");
    }

    public function save()
    {
        $targetCategory = $this->request->getPost('target_category') ?: 'headline';
        if (strtolower($targetCategory) === 'home') {
            $targetCategory = 'headline';
        }
        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Daftar headline kanal '{$targetCategory}' berhasil disimpan.");
    }

    /**
     * POST /headline/reorder
     * Menerima JSON payload: { category: 'headline', order: [12, 45, 89] }
     */
    public function reorder()
    {
        $category = $this->request->getPost('category') ?: 'headline';
        if (strtolower($category) === 'home') {
            $category = 'headline';
        }
        $rawOrder = $this->request->getPost('order');

        if (empty($rawOrder)) {
            $json = $this->request->getJSON(true);
            if (!empty($json['category'])) {
                $category = $json['category'];
            }
            if (!empty($json['order']) && is_array($json['order'])) {
                $rawOrder = $json['order'];
            }
        }

        if (empty($rawOrder) || !is_array($rawOrder)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Format data urutan tidak valid.'
            ]);
        }

        $currentUser = session('user_username') ?: (session('username') ?: (session('user_name') ?: 'admin'));
        $success = $this->headlineModel->batchReorder($category, $rawOrder, $currentUser);

        if ($success) {
            foreach ($rawOrder as $idx => $artId) {
                $pos = $idx + 1;
                $this->headlineModel->logHistory((int)$artId, $category, 'REORDER', $pos, $currentUser, "Mengubah urutan headline ke posisi #{$pos}");
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Urutan headline {$category} berhasil diperbarui."
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON([
            'status'  => 'error',
            'message' => 'Gagal memperbarui urutan headline.'
        ]);
    }

    /**
     * Sub Menu: GET /headline/history
     * Riwayat Audit Log Headline (Siapa yang menambah, mengubah posisi, dan menghapus headline)
     */
    public function history()
    {
        $categoryFilter = $this->request->getGet('category') ?: 'all';
        $actionFilter   = $this->request->getGet('action') ?: 'all';
        $keyword        = $this->request->getGet('q') ?: '';
        $dateFrom       = $this->request->getGet('date_from') ?: '';
        $dateTo         = $this->request->getGet('date_to') ?: '';
        $page           = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage        = 25;

        $filters = [
            'category'  => $categoryFilter,
            'action'    => $actionFilter,
            'q'         => $keyword,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];

        $historyResult = $this->headlineModel->getHistory($filters, $page, $perPage);
        $totalItems = $historyResult['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'from'       => $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'         => min($page * $perPage, $totalItems),
        ];

        return view('headline/history', [
            'title'      => 'Riwayat Log Headline',
            'breadcrumb' => 'Headline / Riwayat Log',
            'logs'       => $historyResult['data'],
            'pagination' => $pagination,
            'filters'    => [
                'category'  => $categoryFilter,
                'action'    => $actionFilter,
                'q'         => $keyword,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]
        ]);
    }
}
