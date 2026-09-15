<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ArticleModel;
use App\Models\AuthorModel;
use App\Models\CategoryModel;
use App\Libraries\ImageProcessor;
use App\Libraries\HtmlSanitizer;

class PostApi extends BaseController
{
    protected ArticleModel $articleModel;
    protected AuthorModel $authorModel;
    protected CategoryModel $categoryModel;
    protected ImageProcessor $imageProcessor;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->authorModel = new AuthorModel();
        $this->categoryModel = new CategoryModel();
        $this->imageProcessor = new ImageProcessor();
    }

    /**
     * POST /api/autosave
     * Background auto-save for editor drafts.
     * Prevents data loss if browser crashes or accidental navigation occurs.
     */
    public function autosave()
    {
        $rawJson = $this->request->getJSON(true);
        $data = $rawJson ?: $this->request->getPost();

        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');

        // Do not autosave completely empty drafts
        if (empty($title) && empty($content)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Naskah masih kosong, auto-save dilewati.'
            ]);
        }

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $kanalName = $data['kanal'] ?? 'Market';
        $rawSubKanal = $data['sub_kanal'] ?? '';
        $subKanal = is_array($rawSubKanal) ? implode(', ', array_filter($rawSubKanal)) : $rawSubKanal;
        
        $authorName = !empty($data['author']) ? $data['author'] : (session('username') ?: 'Redaksi Investor');
        $authorId = $this->authorModel->findOrCreate($authorName);

        // Primary Category is ALWAYS the Master Kanal (parent IS NULL)
        $category = $this->categoryModel->where('categoryName', $kanalName)->where('parent IS NULL')->first();
        if (!$category) {
            $category = $this->categoryModel->where('categoryName', $kanalName)->first();
        }
        $categoryId = $category ? (int)$category['categoryID'] : 1;

        $rawTags = $data['tags'] ?? '';
        $tags = is_array($rawTags) ? $rawTags : array_values(array_filter(array_map('trim', explode(',', $rawTags))));

        $points = [];
        if (!empty($data['points']) && is_array($data['points'])) {
            $points = array_values(array_filter(array_map('trim', $data['points']), fn($p) => $p !== ''));
        }

        $sessionUser = session()->get('user_id') ?: 1;

        $cleanTitle = HtmlSanitizer::cleanTitle($title);
        $cleanSummary = HtmlSanitizer::cleanSummary($data['summary'] ?? '');
        $cleanContent = HtmlSanitizer::clean($content);

        $articleData = [
            'title'             => !empty($cleanTitle) ? $cleanTitle : 'Draft Tanpa Judul (' . date('d M H:i') . ')',
            'slug'              => url_title(!empty($cleanTitle) ? $cleanTitle : 'draft-' . time(), '-', true),
            'summary'           => $cleanSummary,
            'content'           => $cleanContent,
            'status'            => 'Draft',
            'publish_date'      => date('Y-m-d H:i:s'),
            'thumbnail'         => !empty($data['thumbnail']) ? $data['thumbnail'] : '/img/investor_placeholder.jpg',
            'upper_deck'        => $data['upper_deck'] ?? '',
            'source'            => $data['source'] ?? 'Investor.id / Riset',
            'kanal'             => $kanalName,
            'sub_kanal'         => $subKanal,
            'is_premium'        => (int)($data['is_premium'] ?? 0),
            'headline_type'     => $data['headline_type'] ?? 'none',
            'userID'            => $sessionUser,
        ];

        try {
            if ($id && $this->articleModel->find($id)) {
                $this->articleModel->updateArticle($id, $articleData, $authorId, $categoryId, $tags, $points);
                $articleId = $id;
            } else {
                $articleId = $this->articleModel->createArticle($articleData, $authorId, $categoryId, $tags, $points);
            }

            return $this->response->setJSON([
                'success'  => true,
                'id'       => $articleId,
                'saved_at' => date('H:i:s'),
                'message'  => 'Draft tersimpan otomatis'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Auto-save failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan draft: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * POST /api/upload-image
     * Handles file uploads from file picker or Drag & Drop.
     * Standardizes image via ImageProcessor (max 1200px, 85% JPEG).
     */
    public function uploadImage()
    {
        $file = $this->request->getFile('image');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'File gambar tidak ditemukan atau korup.'
            ]);
        }

        // Validate mime type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'error'   => 'Format file tidak didukung. Harap gunakan format JPG, PNG, atau WebP.'
            ]);
        }

        try {
            $webUrl = $this->imageProcessor->processUploadedFile($file, 'cover');

            if (!$webUrl) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'error'   => 'Gagal memproses gambar.'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'url'     => $webUrl,
                'message' => 'Gambar berhasil diunggah dan distandarisasi.'
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error'   => 'Error upload: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * POST /api/post/release-lock/(:num)
     * Dipanggil saat tab browser ditutup atau user berpindah halaman (beforeunload)
     * Tabel: article (W)
     */
    public function releaseLock(int $id)
    {
        $userId = session('user_id');
        if ($userId) {
            $this->articleModel->releaseLock($id, (int)$userId);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Lock released'
        ]);
    }

    /**
     * POST /api/post/force-unlock/(:num)
     * Membuka lock artikel secara paksa (Khusus Administrator & Redpel)
     * Tabel: article (W)
     */
    public function forceUnlock(int $id)
    {
        $userRole = session('user_role') ?? 'Admin';
        if (!in_array(strtolower($userRole), ['administrator', 'redpel', 'admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'error'   => 'Akses ditolak: Hanya Administrator dan Redpel yang dapat melakukan force unlock.'
            ]);
        }

        $this->articleModel->forceUnlock($id);
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Gembok artikel #' . $id . ' berhasil dilepas.'
        ]);
    }

    /**
     * GET /api/post/revision/(:num)
     * Detail riwayat revisi (Before vs After Diff) untuk modal di dalam editor
     */
    public function revisionDetail(int $historyId)
    {
        $detail = $this->articleModel->getRevisionDetail($historyId);
        if (!$detail) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Riwayat revisi tidak ditemukan.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $detail
        ]);
    }
}

