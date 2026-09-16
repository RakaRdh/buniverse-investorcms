<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\KeywordModel;
use App\Models\SeoScoreModel;
use App\Libraries\SeoScorer;
use App\Libraries\GeminiProvider;

class Scoring extends BaseController
{
    protected ArticleModel $articleModel;
    protected KeywordModel $keywordModel;
    protected SeoScoreModel $seoScoreModel;
    protected SeoScorer $seoScorer;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->keywordModel = new KeywordModel();
        $this->seoScoreModel = new SeoScoreModel();
        $this->seoScorer = new SeoScorer();
    }

    /**
     * Dedicated SEO Scoring Page
     * Can receive optional article_id query parameter (from editor page link)
     */
    public function index()
    {
        $selectedArticleId = $this->request->getGet('article_id');
        $selectedArticle = null;
        $scoreHistory = [];

        if (!empty($selectedArticleId) && is_numeric($selectedArticleId)) {
            $selectedArticle = $this->articleModel->find((int)$selectedArticleId);
            if ($selectedArticle) {
                // Fetch keywords
                $keywordsData = $this->keywordModel->getArticleKeywords((int)$selectedArticleId);
                $selectedArticle['keywords'] = array_column($keywordsData, 'keyword');

                // Fetch score history
                $scoreHistory = $this->seoScoreModel->getHistoryByArticleId((int)$selectedArticleId, 5);
            }
        }

        return view('scoring/index', [
            'title'             => 'SEO Readiness Scoring',
            'breadcrumb'        => 'Content / Scoring',
            'selectedArticleId' => $selectedArticleId ? (int)$selectedArticleId : null,
            'selectedArticle'   => $selectedArticle,
            'existingScore'     => null, // Don't auto-show results; user must click button manually
            'scoreHistory'      => $scoreHistory,
        ]);
    }

    /**
     * AJAX endpoint: Search articles for autocomplete / dropdown
     */
    public function searchArticles()
    {
        $q = trim($this->request->getGet('q') ?? '');
        $builder = $this->articleModel->builder()
            ->select('articleID AS id, headline AS title, status, articleDate, slug')
            ->where('status !=', 'Deleted')
            ->orderBy('articleID', 'DESC')
            ->limit(20);

        if (!empty($q)) {
            $builder->groupStart()
                ->like('headline', $q)
                ->orWhere('articleID', (int)$q)
            ->groupEnd();
        }

        $articles = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $articles
        ]);
    }

    /**
     * AJAX endpoint: Fetch single article details for scoring preview
     */
    public function getArticle(int $id)
    {
        $article = $this->articleModel->find($id);
        if (!$article) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Artikel tidak ditemukan.'
            ])->setStatusCode(404);
        }

        $keywordsData = $this->keywordModel->getArticleKeywords($id);
        $keywords = array_column($keywordsData, 'keyword');

        // Fetch metadata
        $db = \Config\Database::connect();
        $metaRows = $db->table('articlemeta')->where('articleID', $id)->get()->getResultArray();
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row['metakey']] = $row['metaval'];
        }

        // Fetch latest score
        $latestScore = $this->seoScoreModel->getLatestByArticleId($id);
        if ($latestScore) {
            $latestScore['details'] = !empty($latestScore['details']) ? json_decode($latestScore['details'], true) : [];
            $latestScore['ai_feedback'] = !empty($latestScore['ai_feedback']) ? json_decode($latestScore['ai_feedback'], true) : null;
            $latestScore['recommendations'] = !empty($latestScore['recommendations']) ? json_decode($latestScore['recommendations'], true) : [];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'article'      => [
                    'id'          => $article['articleID'],
                    'headline'    => $article['headline'],
                    'slug'        => $article['slug'],
                    'summary'     => $article['summary'],
                    'status'      => $article['status'],
                    'articleDate' => $article['articleDate'],
                    'thumbnail'   => $meta['thumbnail'] ?? '/img/investor_placeholder.jpg',
                    'word_count'  => str_word_count(strip_tags($article['body'] ?? '')),
                ],
                'keywords'     => $keywords,
                'latest_score' => $latestScore,
            ]
        ]);
    }

    /**
     * AJAX endpoint: Calculate SEO score for an article
     * Supports skip_ai boolean parameter
     */
    public function calculate()
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $articleId = !empty($input['article_id']) ? (int)$input['article_id'] : 0;
        $skipAi = !empty($input['skip_ai']);

        if ($articleId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Pilih artikel terlebih dahulu untuk diskoring.'
            ])->setStatusCode(400);
        }

        $article = $this->articleModel->find($articleId);
        if (!$article) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Artikel tidak ditemukan di database.'
            ])->setStatusCode(404);
        }

        // Keywords: jika dikirim dari payload editor gunakan itu, jika tidak ambil dari database
        if (!empty($input['keywords']) && is_array($input['keywords'])) {
            $keywords = $input['keywords'];
        } else {
            $keywordsData = $this->keywordModel->getArticleKeywords($articleId);
            $keywords = array_column($keywordsData, 'keyword');
        }

        // Override field artikel dengan naskah real-time editor jika disediakan
        if (!empty($input['headline'])) {
            $article['headline'] = trim($input['headline']);
        }
        if (isset($input['body'])) {
            $article['body'] = $input['body'];
        }
        if (isset($input['summary'])) {
            $article['summary'] = $input['summary'];
        }

        // Metadata
        $db = \Config\Database::connect();
        $metaRows = $db->table('articlemeta')->where('articleID', $articleId)->get()->getResultArray();
        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row['metakey']] = $row['metaval'];
        }
        if (!empty($input['thumbnail'])) {
            $meta['thumbnail'] = $input['thumbnail'];
        }

        try {
            // Run Scoring
            $scoreResult = $this->seoScorer->scoreArticle($article, $keywords, $meta, $skipAi);

            // Save to database
            $userId = session('user_id') ?: 1;
            $saveData = [
                'article_id'      => $articleId,
                'final_score'     => $scoreResult['final_score'],
                'rule_score'      => $scoreResult['rule_score'],
                'ai_score'        => $scoreResult['ai_score'],
                'score_category'  => $scoreResult['score_category'],
                'details'         => json_encode($scoreResult['rule_details']),
                'ai_feedback'     => $scoreResult['ai_feedback'] ? json_encode($scoreResult['ai_feedback']) : null,
                'recommendations' => !empty($scoreResult['recommendations']) ? json_encode($scoreResult['recommendations']) : null,
                'model_used'      => $scoreResult['model_used'],
                'scored_by'       => $userId,
                'used_by'         => $userId,
            ];

            $this->seoScoreModel->insert($saveData);
            $newScoreId = $this->seoScoreModel->getInsertID();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Skor SEO berhasil dihitung dan disimpan.',
                'data'    => array_merge($scoreResult, [
                    'id'         => $newScoreId,
                    'article_id' => $articleId,
                    'created_at' => date('Y-m-d H:i:s'),
                ])
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'SEO Scoring error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Gagal menghitung skor SEO: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * AJAX endpoint: Get scoring history for an article
     */
    public function history(int $articleId)
    {
        $history = $this->seoScoreModel->getHistoryByArticleId($articleId, 10);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $history
        ]);
    }

    /**
     * Submenu: Record Log of all SEO Scorings (Table View)
     */
    public function logs()
    {
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 20;

        $filters = [
            'q'        => trim($this->request->getGet('q') ?? ''),
            'category' => trim($this->request->getGet('category') ?? 'all'),
        ];

        $result = $this->seoScoreModel->getLogsWithRelations($filters, $page, $perPage);
        $totalItems = $result['total'];
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        return view('scoring/logs', [
            'title'      => 'Riwayat Log SEO Scoring',
            'breadcrumb' => 'Content / Scoring / Riwayat Log',
            'logs'       => $result['data'],
            'filters'    => $filters,
            'pagination' => [
                'page'       => $page,
                'perPage'    => $perPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
                'from'       => $totalItems > 0 ? $offset + 1 : 0,
                'to'         => min($offset + $perPage, $totalItems),
            ]
        ]);
    }

    /**
     * AJAX endpoint: Get full details of a specific score log
     */
    public function detail(int $id)
    {
        $score = $this->seoScoreModel->find($id);
        if (!$score) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Log scoring tidak ditemukan.'
            ])->setStatusCode(404);
        }

        $article = $this->articleModel->find((int)$score['article_id']);
        $db = \Config\Database::connect();
        $scoredByUser = !empty($score['scored_by']) 
            ? $db->table('useradministrator')->where('userID', $score['scored_by'])->get()->getRowArray() 
            : null;
        $usedByUser = !empty($score['used_by']) 
            ? $db->table('useradministrator')->where('userID', $score['used_by'])->get()->getRowArray() 
            : null;

        $score['details'] = !empty($score['details']) ? json_decode($score['details'], true) : [];
        $score['ai_feedback'] = !empty($score['ai_feedback']) ? json_decode($score['ai_feedback'], true) : null;
        $score['recommendations'] = !empty($score['recommendations']) ? json_decode($score['recommendations'], true) : [];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'score'          => $score,
                'article'        => $article ? [
                    'id'       => $article['articleID'],
                    'headline' => $article['headline'],
                    'status'   => $article['status'],
                    'slug'     => $article['slug'],
                ] : null,
                'scored_by_name' => $scoredByUser ? ($scoredByUser['name'] ?: $scoredByUser['username']) : 'System',
                'used_by_name'   => $usedByUser ? ($usedByUser['name'] ?: $usedByUser['username']) : ($scoredByUser ? ($scoredByUser['name'] ?: $scoredByUser['username']) : 'System'),
            ]
        ]);
    }

    /**
     * Dedicated Detail Page for a Specific SEO Score Log
     */
    public function detailView(int $id)
    {
        $score = $this->seoScoreModel->find($id);
        if (!$score) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Log audit SEO tidak ditemukan.');
        }

        $article = $this->articleModel->find((int)$score['article_id']);
        $keywordsData = $this->keywordModel->getArticleKeywords((int)$score['article_id']);
        $keywords = array_column($keywordsData, 'keyword');

        $db = \Config\Database::connect();
        $scoredByUser = !empty($score['scored_by']) 
            ? $db->table('useradministrator')->where('userID', $score['scored_by'])->get()->getRowArray() 
            : null;
        $usedByUser = !empty($score['used_by']) 
            ? $db->table('useradministrator')->where('userID', $score['used_by'])->get()->getRowArray() 
            : null;

        $score['details'] = !empty($score['details']) ? json_decode($score['details'], true) : [];
        $score['ai_feedback'] = !empty($score['ai_feedback']) ? json_decode($score['ai_feedback'], true) : null;
        $score['recommendations'] = !empty($score['recommendations']) ? json_decode($score['recommendations'], true) : [];

        // Fetch score history for the article
        $scoreHistory = $this->seoScoreModel->getHistoryByArticleId((int)$score['article_id'], 5);

        return view('scoring/detail', [
            'title'          => 'Detail Audit SEO #' . $score['id'],
            'breadcrumb'     => 'Content / Scoring / Riwayat Log / Detail #' . $score['id'],
            'score'          => $score,
            'article'        => $article,
            'keywords'       => $keywords,
            'scoredByName'   => $scoredByUser ? ($scoredByUser['name'] ?: $scoredByUser['username']) : 'System',
            'usedByName'     => $usedByUser ? ($usedByUser['name'] ?: $usedByUser['username']) : ($scoredByUser ? ($scoredByUser['name'] ?: $scoredByUser['username']) : 'System'),
            'scoreHistory'   => $scoreHistory,
        ]);
    }
}

