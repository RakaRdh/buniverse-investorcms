<?php

namespace App\Controllers;

use App\Models\TrendingModel;
use App\Models\TrendingExcludeModel;

/**
 * Class Trending
 * 
 * @category Discontinued / Archival Track Record Controller
 * 
 * Modul ini berstatus DISCONTINUED dalam alur editorial aktif harian, karena redaksi
 * telah beralih 100% menggunakan kurasi Headline kategori 'trending' (`tblArticleHeadline`).
 * Menu modul ini sengaja TIDAK DITAMPILKAN di sidebar CMS.
 *
 * Kode fungsional ini disediakan secara lengkap sebagai TRACK RECORD operasional dan
 * aksesibilitas data historis ke tabel `tblTrending` dan `tblTrendingExclude`.
 */
class Trending extends BaseController
{
    protected TrendingModel $trendingModel;
    protected TrendingExcludeModel $trendingExcludeModel;

    public function __construct()
    {
        $this->trendingModel = new TrendingModel();
        $this->trendingExcludeModel = new TrendingExcludeModel();
    }

    /**
     * Mengambil daftar trending topics (API/JSON untuk track record arsip)
     */
    public function index()
    {
        $trending = $this->trendingModel->getTrending(0, 0, 50);
        $excludes = $this->trendingExcludeModel->findAll();

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setJSON([
                'status'   => 'success',
                'notice'   => 'Modul ini berstatus Discontinued dan disimpan untuk track record.',
                'trending' => $trending,
                'excludes' => $excludes,
            ]);
        }

        // Jika diakses via browser langsung, beri informasi dan redirect ke headline trending
        return redirect()->to('/headline?category=trending')->with('info', 'Pengelolaan topik trending telah terintegrasi langsung pada menu Headline kategori Trending.');
    }

    /**
     * Menyimpan data trending topic (Replika track record: Article::saveTrendingTopic)
     */
    public function save()
    {
        $data = $this->request->getPost();
        if (!empty($data['label'])) {
            $this->trendingModel->saveTrendingTopic($data);
            return redirect()->to('/headline?category=trending')->with('success', 'Data trending topic historis berhasil disimpan.');
        }

        return redirect()->to('/headline?category=trending')->with('error', 'Label trending topic tidak boleh kosong.');
    }

    /**
     * Menghapus trending topic
     */
    public function delete($id = null)
    {
        if ($id) {
            $this->trendingModel->deleteTrending((int)$id);
            return redirect()->to('/headline?category=trending')->with('success', 'Trending topic berhasil dihapus.');
        }

        return redirect()->to('/headline?category=trending');
    }

    /**
     * Menambahkan pengecualian artikel
     */
    public function addExclude()
    {
        $articleId = (int)$this->request->getPost('articleID');
        $trendingId = (int)$this->request->getPost('trendingID');

        if ($articleId && $trendingId) {
            $this->trendingExcludeModel->addExclude($articleId, $trendingId);
            return redirect()->to('/headline?category=trending')->with('success', 'Artikel berhasil dikecualikan.');
        }

        return redirect()->to('/headline?category=trending')->with('error', 'Parameter artikel atau trending tidak valid.');
    }
}
