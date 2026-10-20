<?php

namespace App\Models;

use CodeIgniter\Model;

class KeywordModel extends Model
{
    protected $table = 'Keyword';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'keyword',
        'slug'
    ];
    protected $useTimestamps = false;

    /**
     * Synchronize keywords for article
     * Replaces the old TagModel::syncArticleTags() functionality
     */
    public function syncArticleKeywords(int $articleId, array $keywords): void
    {
        $this->db->table('ArticleKeyword')
            ->where('articleID', $articleId)
            ->delete();

        $insertedIds = [];
        foreach ($keywords as $kw) {
            $kw = trim(trim($kw), " \t\n\r\0\x0B*•-");
            if (empty($kw)) continue;

            $slug = url_title($kw, '-', true);
            if (empty($slug)) continue;

            $existing = $this->where('slug', $slug)->orWhere('keyword', $kw)->first();

            if (!$existing) {
                $this->insert([
                    'keyword' => $kw,
                    'slug'    => $slug,
                ]);
                $kwId = $this->insertID();
            } else {
                $kwId = $existing['id'];
            }

            if (!in_array($kwId, $insertedIds)) {
                $this->db->table('ArticleKeyword')->insert([
                    'articleID'  => $articleId,
                    'keywordID'  => (int)$kwId,
                ]);
                $insertedIds[] = $kwId;
            }
        }
    }

    /**
     * Get keywords for a specific article
     */
    public function getArticleKeywords(int $articleId): array
    {
        return $this->db->table('ArticleKeyword akw')
            ->select('k.id AS keywordID, k.keyword, k.slug')
            ->join('Keyword k', 'k.id = akw.keywordID')
            ->where('akw.articleID', $articleId)
            ->get()->getResultArray();
    }

    /**
     * Get all keywords with article count for CRUD page
     * Tabel: keyword (R), articlekeyword (R)
     */
    public function getKeywordsWithCount(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('Keyword')
            ->select('id, keyword, slug');

        if (!empty($filters['q'])) {
            $builder->groupStart()
                ->like('keyword', $filters['q'])
                ->orLike('slug', $filters['q'])
                ->groupEnd();
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $offset = ($page - 1) * $perPage;
        $builder->orderBy('id', 'DESC');
        $keywords = $builder->limit($perPage, $offset)->get()->getResultArray();

        if (!empty($keywords)) {
            $keywordIds = array_column($keywords, 'id');
            $counts = $this->db->table('ArticleKeyword')
                ->select('keywordID, COUNT(articleID) AS article_count')
                ->whereIn('keywordID', $keywordIds)
                ->groupBy('keywordID')
                ->get()->getResultArray();

            $countMap = [];
            foreach ($counts as $c) {
                $countMap[$c['keywordID']] = (int)$c['article_count'];
            }

            foreach ($keywords as &$kw) {
                $kw['article_count'] = $countMap[$kw['id']] ?? 0;
            }
            unset($kw);
        }

        return [
            'data'  => $keywords,
            'total' => $total,
        ];
    }

    /**
     * Delete keyword and unlink from all articles
     * Tabel: keyword (D), articlekeyword (D)
     */
    public function deleteKeyword(int $keywordId): bool
    {
        $this->db->transStart();
        $this->db->table('ArticleKeyword')->where('keywordID', $keywordId)->delete();
        $this->where('id', $keywordId)->delete();
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Scan and retrieve relevant keyword candidates from database based on article text
     * Returns a flat list of keyword strings to guide AI generation
     */
    public function getRelevantCandidates(string $text, int $limit = 80): array
    {
        $cleanText = strip_tags($text);

        // Stop words in Indonesian common in editorial text
        $stopWords = [
            'yang', 'untuk', 'dengan', 'dalam', 'karena', 'bahwa', 'tersebut', 'kepada',
            'sebagai', 'antara', 'menurut', 'adalah', 'mereka', 'seperti', 'terhadap',
            'melalui', 'tentang', 'setelah', 'sebelum', 'selama', 'hingga', 'sampai',
            'kemudian', 'sedangkan', 'padahal', 'bahkan', 'namun', 'tetapi', 'melainkan',
            'ketika', 'saat', 'waktu', 'secara', 'sangat', 'lebih', 'paling', 'sudah',
            'telah', 'sedang', 'akan', 'dapat', 'bisa', 'harus', 'mampu', 'tidak', 'bukan'
        ];

        preg_match_all('/[a-zA-Z0-9]{4,}/u', $cleanText, $matches);
        $words = $matches[0] ?? [];
        $uniqueWords = [];
        foreach ($words as $w) {
            $wLower = strtolower($w);
            if (!in_array($wLower, $stopWords) && !isset($uniqueWords[$wLower])) {
                $uniqueWords[$wLower] = true;
                if (count($uniqueWords) >= 25) break;
            }
        }

        $candidates = [];

        // 1. Query keywords matching significant content tokens
        if (!empty($uniqueWords)) {
            $builder = $this->db->table('Keyword')->select('keyword')->groupStart();
            $first = true;
            foreach (array_keys($uniqueWords) as $token) {
                if ($first) {
                    $builder->like('keyword', $token);
                    $first = false;
                } else {
                    $builder->orLike('keyword', $token);
                }
            }
            $builder->groupEnd();

            $tokenMatches = $builder->limit($limit)->get()->getResultArray();
            foreach ($tokenMatches as $row) {
                if (!empty($row['keyword'])) {
                    $candidates[$row['keyword']] = true;
                }
            }
        }

        // 2. Also retrieve top frequently used / popular keywords if candidate list is small
        if (count($candidates) < $limit) {
            $remaining = $limit - count($candidates);
            $popular = $this->db->table('Keyword k')
                ->select('k.keyword, COUNT(ak.articleID) as cnt')
                ->join('ArticleKeyword ak', 'ak.keywordID = k.id', 'left')
                ->groupBy('k.id')
                ->orderBy('cnt', 'DESC')
                ->orderBy('k.id', 'DESC')
                ->limit($remaining)
                ->get()->getResultArray();

            foreach ($popular as $row) {
                if (!empty($row['keyword'])) {
                    $candidates[$row['keyword']] = true;
                }
            }
        }

        return array_keys($candidates);
    }
}
