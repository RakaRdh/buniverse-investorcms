<?php

namespace App\Models;

use CodeIgniter\Model;

class SeoScoreModel extends Model
{
    protected $table            = 'SeoScores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'article_id',
        'final_score',
        'rule_score',
        'ai_score',
        'score_category',
        'details',
        'ai_feedback',
        'recommendations',
        'model_used',
        'scored_by',
        'used_by',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get the latest SEO score for a specific article
     */
    public function getLatestByArticleId(int $articleId): ?array
    {
        return $this->where('article_id', $articleId)
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    /**
     * Get history of scores for a specific article
     */
    public function getHistoryByArticleId(int $articleId, int $limit = 10): array
    {
        return $this->where('article_id', $articleId)
                    ->orderBy('id', 'DESC')
                    ->findAll($limit);
    }

    /**
     * Get paginated scoring logs with joined article and user details
     */
    public function getLogsWithRelations(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $builder = $this->db->table('SeoScores s')
            ->select('
                s.id,
                s.article_id,
                s.final_score,
                s.rule_score,
                s.ai_score,
                s.score_category,
                s.model_used,
                s.scored_by,
                s.used_by,
                s.created_at,
                a.headline AS article_title,
                a.slug AS article_slug,
                a.status AS article_status,
                u.name AS scored_by_name,
                u.username AS scored_by_username,
                uu.name AS used_by_name,
                uu.username AS used_by_username
            ')
            ->join('Article a', 'a.articleID = s.article_id', 'left')
            ->join('UserAdministrator u', 'u.userID = s.scored_by', 'left')
            ->join('UserAdministrator uu', 'uu.userID = s.used_by', 'left')
            ->orderBy('s.id', 'DESC');

        if (!empty($filters['q'])) {
            $builder->groupStart()
                ->like('a.headline', $filters['q'])
                ->orWhere('s.article_id', (int)$filters['q'])
                ->orLike('u.name', $filters['q'])
                ->orLike('uu.name', $filters['q'])
            ->groupEnd();
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $builder->where('s.score_category', $filters['category']);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults();

        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        return [
            'data'  => $builder->get()->getResultArray(),
            'total' => $total,
        ];
    }
}
