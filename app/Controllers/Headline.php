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

        // Selected target category (defaults to 'home')
        $targetCategory = $this->request->getGet('target_category') ?: 'home';
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
        $allArticles = $this->articleModel->getArticlesWithRelations($filters, $page, $perPage);
        $availablePosts = $allArticles['data'];
        $totalItems = $allArticles['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        // Create quick lookup map for active headline articles
        $activeHeadlineMap = [];
        foreach ($headlines as $hl) {
            $activeHeadlineMap[(int)$hl['id']] = (int)$hl['headline_position'];
        }

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'from'       => $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'         => min($page * $perPage, $totalItems),
        ];

        return view('headline/index', [
            'title'             => 'Manajemen Headline Redaksi',
            'breadcrumb'        => 'Content / Headline',
            'categories'        => $categories,
            'targetCategory'    => $targetCategory,
            'headlines'         => $headlines,
            'availablePosts'    => $availablePosts,
            'activeHeadlineMap' => $activeHeadlineMap,
            'pagination'        => $pagination,
            'filters'           => [
                'q'        => $keyword ?? '',
                'category' => $categoryFilter ?? 'all'
            ]
        ]);
    }

    public function add()
    {
        $articleId = (int)$this->request->getPost('article_id');
        $targetCategory = $this->request->getPost('target_category') ?: 'home';

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
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('error', "Artikel ini sudah aktif berada di posisi #{$existing['num']} antrean headline {$targetCategory}.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $currentHeadlines = $this->headlineModel->getActiveHeadlines($targetCategory);
        $totalActive = count($currentHeadlines);

        // 2. Strict Quota: Max 5 articles per category
        if ($totalActive >= 5) {
            $db->transRollback();
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('error', "Batas maksimal 5 artikel headline untuk kategori '{$targetCategory}' sudah tercapai. Silakan hapus salah satu headline terlebih dahulu.");
        }

        $currentUser = session('user_username') ?: (session('username') ?: (session('user_name') ?: 'admin'));
        $targetPosition = $totalActive + 1;

        if ($existing) {
            // Reactivate previous entry
            $this->headlineModel
                ->where('id', $existing['id'])
                ->set([
                    'num'       => $targetPosition,
                    'status'    => 1,
                    'set_by'    => $currentUser,
                    'timestamp' => date('Y-m-d H:i:s'),
                ])
                ->update();

            $this->headlineModel->logHistory($articleId, $targetCategory, 'ADD', $targetPosition, $currentUser, "Mengaktifkan kembali artikel ke headline posisi #{$targetPosition}");

            $db->transComplete();
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel berhasil ditambahkan ke antrean headline {$targetCategory} di posisi #{$targetPosition}!");
        }

        // Fresh insertion
        $this->headlineModel->insert([
            'category'  => $targetCategory,
            'articleID' => $articleId,
            'num'       => $targetPosition,
            'timestamp' => date('Y-m-d H:i:s'),
            'status'    => 1,
            'set_by'    => $currentUser,
        ]);

        $this->headlineModel->logHistory($articleId, $targetCategory, 'ADD', $targetPosition, $currentUser, "Menambahkan artikel ke headline posisi #{$targetPosition}");

        $db->transComplete();
        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel berhasil ditambahkan ke antrean headline {$targetCategory} di posisi #{$targetPosition}!");
    }

    public function remove($id)
    {
        $targetCategory = $this->request->getGet('target_category') ?: 'home';

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
        $targetCategory = $this->request->getPost('target_category') ?: 'home';
        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Daftar headline kanal '{$targetCategory}' berhasil disimpan.");
    }

    /**
     * POST /headline/reorder
     * Menerima JSON payload: { category: 'home', order: [12, 45, 89] }
     */
    public function reorder()
    {
        $category = $this->request->getPost('category') ?: 'home';
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
