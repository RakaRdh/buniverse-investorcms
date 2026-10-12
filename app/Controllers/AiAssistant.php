<?php

namespace App\Controllers;

use App\Libraries\GeminiProvider;

class AiAssistant extends BaseController
{
    protected GeminiProvider $gemini;

    public function __construct()
    {
        $this->gemini = new GeminiProvider();
    }

    public function generateTitle()
    {
        $input = $this->parseInput();
        $body = $input['body'] ?? '';
        $articleId = !empty($input['article_id']) ? (int)$input['article_id'] : null;

        if (empty(trim($body))) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => 'Isi artikel berita (body) diperlukan untuk menghasilkan judul.'
            ])->setStatusCode(400);
        }

        $startTime = microtime(true);
        try {
            $title = $this->gemini->generateTitle($body);
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $logId = $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'title',
                'input_title'        => null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => $title,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => null
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'title'  => $title,
                    'model'  => $this->gemini->getLastModelUsed(),
                    'log_id' => $logId,
                ]
            ]);
        } catch (\Throwable $e) {
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'title',
                'input_title'        => null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => null,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => $e->getMessage()
            ]);

            return $this->response->setJSON([
                'status' => 'error',
                'data'   => (ENVIRONMENT === 'development') ? 'Gagal menghasilkan judul: ' . $e->getMessage() : 'Layanan AI sedang sibuk. Silakan coba beberapa saat lagi.'
            ])->setStatusCode(500);
        }
    }

    public function generateSummary()
    {
        $input = $this->parseInput();
        $title = $input['title'] ?? '';
        $body = $input['body'] ?? '';
        $articleId = !empty($input['article_id']) ? (int)$input['article_id'] : null;

        if (empty(trim($body))) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => 'Isi artikel berita (body) diperlukan untuk merangkum ringkasan.'
            ])->setStatusCode(400);
        }

        $startTime = microtime(true);
        try {
            $summary = $this->gemini->generateSummary($title, $body);
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $logId = $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'summary',
                'input_title'        => $title,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => $summary,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => null
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'summary' => $summary,
                    'model'   => $this->gemini->getLastModelUsed(),
                    'log_id'  => $logId,
                ]
            ]);
        } catch (\Throwable $e) {
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'summary',
                'input_title'        => $title,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => null,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => $e->getMessage()
            ]);

            return $this->response->setJSON([
                'status' => 'error',
                'data'   => (ENVIRONMENT === 'development') ? 'Gagal merangkum ringkasan: ' . $e->getMessage() : 'Layanan AI sedang sibuk. Silakan coba beberapa saat lagi.'
            ])->setStatusCode(500);
        }
    }

    public function generateTags()
    {
        $input = $this->parseInput();
        $title = $input['title'] ?? '';
        $body = $input['body'] ?? '';
        $articleId = !empty($input['article_id']) ? (int)$input['article_id'] : null;

        if (empty(trim($body))) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => 'Isi artikel berita (body) diperlukan untuk mengekstrak tags.'
            ])->setStatusCode(400);
        }

        $startTime = microtime(true);
        try {
            $keywordModel = new \App\Models\KeywordModel();
            
            // 1. Scan DB for relevant keyword candidates based on title & body content
            $candidateKeywords = $keywordModel->getRelevantCandidates(trim($title . ' ' . $body), 80);

            // 2. Call Gemini with candidates grounding
            $rawTags = $this->gemini->generateTags($body, $title, $candidateKeywords);

            // 3. Classify which tags matched existing keywords vs newly generated
            $matchedFromDb = [];
            $newGenerated = [];
            $finalTags = [];

            if (!empty($rawTags)) {
                $existingRows = $keywordModel->select('id, keyword, slug')
                    ->whereIn('keyword', $rawTags)
                    ->findAll();

                $existingMap = [];
                foreach ($existingRows as $row) {
                    $existingMap[mb_strtolower(trim($row['keyword']))] = $row['keyword'];
                }

                foreach ($rawTags as $tag) {
                    $tagClean = trim($tag);
                    if (empty($tagClean)) continue;

                    $tagLower = mb_strtolower($tagClean);
                    if (isset($existingMap[$tagLower])) {
                        $canonical = $existingMap[$tagLower];
                        $matchedFromDb[] = $canonical;
                        $finalTags[] = $canonical;
                    } else {
                        $newGenerated[] = $tagClean;
                        $finalTags[] = $tagClean;
                    }
                }
            }

            $finalTags = array_values(array_unique($finalTags));
            $matchedFromDb = array_values(array_unique($matchedFromDb));
            $newGenerated = array_values(array_unique($newGenerated));

            $latency = (int)((microtime(true) - $startTime) * 1000);
            $logId = $this->logAiGeneration([
                'article_id'          => $articleId,
                'action_type'         => 'tags',
                'input_title'         => $title ?: null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'    => implode(', ', $finalTags),
                'model_name'          => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'          => $latency,
                'error_message'       => null
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'tags'             => $finalTags,
                    'matched_from_db'  => $matchedFromDb,
                    'new_generated'    => $newGenerated,
                    'candidates_count' => count($candidateKeywords),
                    'model'            => $this->gemini->getLastModelUsed(),
                    'log_id'           => $logId,
                ]
            ]);
        } catch (\Throwable $e) {
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logAiGeneration([
                'article_id'          => $articleId,
                'action_type'         => 'tags',
                'input_title'         => $title ?: null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'    => null,
                'model_name'          => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'          => $latency,
                'error_message'       => $e->getMessage()
            ]);

            return $this->response->setJSON([
                'status' => 'error',
                'data'   => (ENVIRONMENT === 'development') ? 'Gagal mengekstrak tags: ' . $e->getMessage() : 'Layanan AI sedang sibuk. Silakan coba beberapa saat lagi.'
            ])->setStatusCode(500);
        }
    }

    public function generatePoints()
    {
        $input = $this->parseInput();
        $body = $input['body'] ?? '';
        $articleId = !empty($input['article_id']) ? (int)$input['article_id'] : null;

        if (empty(trim($body))) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => 'Isi artikel berita (body) diperlukan untuk menghasilkan poin penting.'
            ])->setStatusCode(400);
        }

        $startTime = microtime(true);
        try {
            $points = $this->gemini->generatePoints($body);
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $logId = $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'points',
                'input_title'        => null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => $points,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => null
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'points' => $points,
                    'model'  => $this->gemini->getLastModelUsed(),
                    'log_id' => $logId,
                ]
            ]);
        } catch (\Throwable $e) {
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logAiGeneration([
                'article_id'         => $articleId,
                'action_type'        => 'points',
                'input_title'        => null,
                'input_body_snapshot' => mb_substr($body, 0, 500),
                'generated_result'   => null,
                'model_name'         => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'         => $latency,
                'error_message'      => $e->getMessage()
            ]);

            return $this->response->setJSON([
                'status' => 'error',
                'data'   => (ENVIRONMENT === 'development') ? 'Gagal menghasilkan poin penting: ' . $e->getMessage() : 'Layanan AI sedang sibuk. Silakan coba beberapa saat lagi.'
            ])->setStatusCode(500);
        }
    }

    public function scrape()
    {
        $input = $this->parseInput();
        $url = trim($input['url'] ?? '');

        if (empty($url)) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => 'URL artikel berita eksternal diperlukan untuk melakukan scraping.'
            ])->setStatusCode(400);
        }

        $startTime = microtime(true);
        try {
            $data = $this->gemini->scrapeArticle($url);
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $logId = $this->logAiGeneration([
                'article_id'          => null,
                'action_type'         => 'scrape',
                'input_title'         => 'SCRAPE: ' . $url,
                'input_body_snapshot' => $url,
                'generated_result'    => mb_substr($data['body'] ?? '', 0, 1000),
                'model_name'          => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'          => $latency,
                'error_message'       => null
            ]);
            $data['log_id'] = $logId;

            // Auto-register aset gambar scrape ke tabel media database dengan watermark
            $mediaId = 0;
            if (!empty($data['thumbnail_url']) && $data['thumbnail_url'] !== '/img/investor_placeholder.jpg') {
                try {
                    $db = \Config\Database::connect();
                    $parsedUrl = parse_url($url);
                    $sourceHost = $parsedUrl['host'] ?? 'Web';
                    $currentUser = session('user_name') ?: (session('user_username') ?: 'Redaksi');
                    $currentUserId = (int)(session('user_id') ?: 1);

                    $mediaFilename = $data['thumbnail_filename'] ?? ltrim(str_replace('/uploads/media/', '', $data['thumbnail_url']), '/');
                    $mediaTitle = 'AI Scrape - ' . $sourceHost . ' (' . date('d M H:i') . ')';

                    $db->table('media')->insert([
                        'mediaTitle'  => $mediaTitle,
                        'caption'     => $data['thumbnail_caption'] ?? '',
                        'description' => $data['thumbnail_description'] ?? ($data['thumbnail_caption'] ?? ''),
                        'filename'    => $mediaFilename,
                        'source'      => $sourceHost . ' (AI Scraped)',
                        'author'      => $currentUser . ' (AI Scrape)',
                        'userID'      => $currentUserId,
                        'timestamp'   => date('Y-m-d H:i:s'),
                    ]);
                    $mediaId = (int)$db->insertID();

                    // Catat jejak audit editorlog
                    try {
                        $activityLog = new \App\Models\ActivityLogModel();
                        $activityLog->logAction($currentUserId, 'insert', 'foto', [
                            'mediaTitle' => $mediaTitle,
                            'caption'    => $data['thumbnail_caption'] ?? '',
                            'source_url' => $url,
                        ]);
                    } catch (\Throwable $e) {}
                } catch (\Throwable $e) {
                    log_message('error', 'Auto-register media for scrape failed: ' . $e->getMessage());
                }
            }

            $data['media_id'] = $mediaId;

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $data
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'data'   => $e->getMessage()
            ])->setStatusCode(400);
        } catch (\Throwable $e) {
            $latency = (int)((microtime(true) - $startTime) * 1000);
            $this->logAiGeneration([
                'article_id'          => null,
                'action_type'         => 'scrape',
                'input_title'         => 'SCRAPE: ' . $url,
                'input_body_snapshot' => $url,
                'generated_result'    => null,
                'model_name'          => $this->gemini->getLastModelUsed() ?: $this->gemini->getPrimaryModel(),
                'latency_ms'          => $latency,
                'error_message'       => $e->getMessage()
            ]);

            return $this->response->setJSON([
                'status' => 'error',
                'data'   => (ENVIRONMENT === 'development') ? 'Gagal melakukan scraping artikel: ' . $e->getMessage() : 'Layanan scraping sedang sibuk atau URL target tidak dapat dijangkau.'
            ])->setStatusCode(500);
        }
    }

    /**
     * Safely parse request body JSON or Form-Data
     */
    protected function parseInput(): array
    {
        $request = $this->request ?? \Config\Services::request();
        try {
            $json = $request->getJSON(true);
            if (is_array($json) && !empty($json)) {
                return $json;
            }
        } catch (\Throwable $e) {}

        $raw = $request->getBody();
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }

        $post = $request->getPost();
        if (is_array($post) && !empty($post)) {
            return $post;
        }

        return [];
    }

    /**
     * Log AI interaction to ai_generated_logs table (Pure standalone, querying DB)
     */
    protected function logAiGeneration(array $data): ?int
    {
        try {
            $db = \Config\Database::connect();
            $userId = session('user_id') ?: 1;

            $record = [
                'article_id'              => $data['article_id'] ?? null,
                'user_id'                 => $userId,
                'action_type'             => $data['action_type'] ?? 'general',
                'provider_code'           => $data['provider_code'] ?? 'gemini',
                'model_name'              => $data['model_name'] ?? 'gemini-3.5-flash-lite',
                'input_title'             => $data['input_title'] ?? null,
                'input_body_snapshot'     => $data['input_body_snapshot'] ?? null,
                'generated_result'        => $data['generated_result'] ?? null,
                'was_accepted'            => $data['was_accepted'] ?? null,
                'latency_ms'              => $data['latency_ms'] ?? null,
                'error_message'           => $data['error_message'] ?? null,
                'created_at'              => date('Y-m-d H:i:s'),
            ];

            $builder = $db->table('ai_generated_logs');
            $builder->insert($record);
            return $db->insertID();
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert into ai_generated_logs: ' . $e->getMessage());
            return null;
        }
    }
}
