<?php

namespace App\Models;

use CodeIgniter\Model;

class KeywordModel extends Model
{
    protected $table = 'keyword';
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
        $this->db->table('articlekeyword')
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
                $this->db->table('articlekeyword')->insert([
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
        return $this->db->table('articlekeyword akw')
            ->select('k.id AS keywordID, k.keyword, k.slug')
            ->join('keyword k', 'k.id = akw.keywordID')
            ->where('akw.articleID', $articleId)
            ->get()->getResultArray();
    }

    /**
     * Get all keywords with article count for CRUD page
     * Tabel: keyword (R), articlekeyword (R)
     */
    public function getKeywordsWithCount(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('keyword k')
            ->select('k.id, k.keyword, k.slug, COUNT(akw.articleID) AS article_count')
            ->join('articlekeyword akw', 'akw.keywordID = k.id', 'left')
            ->groupBy('k.id');

        if (!empty($filters['q'])) {
            $builder->like('k.keyword', $filters['q'])
                    ->orLike('k.slug', $filters['q']);
        }

        $countBuilder = clone $builder;
        $total = count($countBuilder->get()->getResultArray());

        $builder->orderBy('article_count', 'DESC')
                ->orderBy('k.keyword', 'ASC');

        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)->get()->getResultArray();

        return [
            'data'  => $data,
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
        $this->db->table('articlekeyword')->where('keywordID', $keywordId)->delete();
        $this->where('id', $keywordId)->delete();
        $this->db->transComplete();

        return $this->db->transStatus();
    }
}
