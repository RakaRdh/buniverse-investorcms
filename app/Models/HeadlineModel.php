<?php

namespace App\Models;

use CodeIgniter\Model;

class HeadlineModel extends Model
{
    protected $table = 'ArticleHeadline';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'category',
        'articleID',
        'num',
        'timestamp',
        'status',
        'set_by'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Active headlines with article details and author
     */
    public function getActiveHeadlines(?string $label = null): array
    {
        $builder = $this->db->table('ArticleHeadline h')
            ->select('
                n.articleID AS id,
                n.headline AS title,
                n.slug AS slug,
                n.summary AS summary,
                n.viewed AS views,
                n.status AS status,
                n.articleDate AS publish_date,
                n.createdAt AS create_date,
                h.num AS headline_position,
                h.category AS category,
                h.timestamp AS headline_set_at,
                h.set_by AS set_by,
                COALESCE(
                    NULLIF(ANY_VALUE(m.filename), ""),
                    "/img/investor_placeholder.jpg"
                ) AS thumbnail,
                COALESCE(ANY_VALUE(nm_deck.metaval), "") AS upper_deck,
                CASE 
                    WHEN LOWER(COALESCE(ANY_VALUE(nm_prem.metaval), "")) IN ("true", "1") THEN "1"
                    ELSE "0"
                END AS is_premium,
                GROUP_CONCAT(DISTINCT a.authorName SEPARATOR ", ") AS author,
                ANY_VALUE(p.username) AS post_by
            ')
            ->join('Article n', 'n.articleID = h.articleID')
            ->join('UserAdministrator p', 'p.userID = n.userID', 'left')
            ->join('ArticleAuthor nal', 'nal.articleID = n.articleID', 'left')
            ->join('Authors a', 'a.authorID = nal.authorID', 'left')
            ->join('MediaArticle ma_thumb', 'ma_thumb.articleID = n.articleID AND ma_thumb.position = 1', 'left')
            ->join('Media m', 'm.mediaID = ma_thumb.mediaID', 'left')
            ->join('ArticleMeta nm_deck', 'nm_deck.articleID = n.articleID AND nm_deck.metakey = "upper_deck"', 'left')
            ->join('ArticleMeta nm_prem', 'nm_prem.articleID = n.articleID AND nm_prem.metakey = "premium"', 'left');

        $builder->where('h.status', 1);

        if (!empty($label) && strtolower($label) !== 'all') {
            $builder->where('LOWER(h.category)', strtolower($label));
        }

        return $builder->groupBy(['h.id', 'n.articleID'])
            ->orderBy('h.num', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Batch reorder headline positions
     * Replika legacy: Article::saveHeadline() reordering
     * Tabel: articleheadline (W)
     */
    public function batchReorder(string $category, array $orderedArticleIds, ?string $setBy = null): bool
    {
        if (empty($orderedArticleIds)) {
            return false;
        }

        $this->db->transStart();
        foreach ($orderedArticleIds as $position => $articleId) {
            $updateData = ['num' => $position + 1];
            if ($setBy !== null) {
                $updateData['set_by'] = $setBy;
                $updateData['timestamp'] = date('Y-m-d H:i:s');
            }
            $this->db->table('ArticleHeadline')
                ->where('articleID', (int)$articleId)
                ->where('category', $category)
                ->where('status', 1)
                ->update($updateData);
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Log headline activity (ADD, REMOVE, REORDER) to articleheadlinehistory
     */
    public function logHistory(int $articleId, string $category, string $action, ?int $position = null, ?string $user = null, ?string $notes = null): bool
    {
        return $this->db->table('ArticleHeadlineHistory')->insert([
            'articleID' => $articleId,
            'category'  => $category,
            'action'    => strtoupper($action),
            'position'  => $position,
            'user'      => $user ?: (session('user_username') ?: (session('username') ?: 'system')),
            'timestamp' => date('Y-m-d H:i:s'),
            'notes'     => $notes,
        ]);
    }

    /**
     * Get paginated headline history with article and user details
     */
    public function getHistory(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->db->table('ArticleHeadlineHistory hh')
            ->select('
                hh.id AS history_id,
                hh.articleID,
                hh.category,
                hh.action,
                hh.position,
                hh.user,
                hh.timestamp,
                hh.notes,
                n.headline AS article_title,
                n.status AS article_status,
                u.name AS actor_name,
                u.alias AS actor_alias
            ')
            ->join('Article n', 'n.articleID = hh.articleID', 'left')
            ->join('UserAdministrator u', 'u.username = hh.user', 'left');

        if (!empty($filters['category']) && strtolower($filters['category']) !== 'all') {
            $builder->where('LOWER(hh.category)', strtolower($filters['category']));
        }

        if (!empty($filters['action']) && strtolower($filters['action']) !== 'all') {
            $builder->where('UPPER(hh.action)', strtoupper($filters['action']));
        }

        if (!empty($filters['q'])) {
            $builder->groupStart()
                ->like('n.headline', $filters['q'])
                ->orLike('hh.user', $filters['q'])
                ->orLike('u.name', $filters['q'])
            ->groupEnd();
        }

        if (!empty($filters['date_from'])) {
            $builder->where('hh.timestamp >=', date('Y-m-d 00:00:00', strtotime($filters['date_from'])));
        }
        if (!empty($filters['date_to'])) {
            $builder->where('hh.timestamp <=', date('Y-m-d 23:59:59', strtotime($filters['date_to'])));
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $offset = ($page - 1) * $perPage;
        $list = $builder->orderBy('hh.timestamp', 'DESC')
            ->limit($perPage, $offset)
            ->get()->getResultArray();

        return [
            'total' => $total,
            'data'  => $list,
        ];
    }
}
