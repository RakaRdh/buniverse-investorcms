<?php

namespace App\Models;

use CodeIgniter\Model;

class HeadlineModel extends Model
{
    protected $table = 'articleheadline';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'category',
        'articleID',
        'num',
        'timestamp',
        'status'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Active headlines with article details and author
     */
    public function getActiveHeadlines(?string $label = null): array
    {
        $builder = $this->db->table('articleheadline h')
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
                COALESCE(ANY_VALUE(nm_thumb.metaval), "/img/investor_placeholder.jpg") AS thumbnail,
                COALESCE(ANY_VALUE(nm_deck.metaval), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.metaval), "0") AS is_premium,
                GROUP_CONCAT(DISTINCT a.authorName SEPARATOR ", ") AS author,
                ANY_VALUE(p.username) AS post_by
            ')
            ->join('article n', 'n.articleID = h.articleID')
            ->join('useradministrator p', 'p.userID = n.userID', 'left')
            ->join('articleauthor nal', 'nal.articleID = n.articleID', 'left')
            ->join('authors a', 'a.authorID = nal.authorID', 'left')
            ->join('articlemeta nm_thumb', 'nm_thumb.articleID = n.articleID AND nm_thumb.metakey = "thumbnail"', 'left')
            ->join('articlemeta nm_deck', 'nm_deck.articleID = n.articleID AND nm_deck.metakey = "upper_deck"', 'left')
            ->join('articlemeta nm_prem', 'nm_prem.articleID = n.articleID AND nm_prem.metakey = "is_premium"', 'left');

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
    public function batchReorder(string $category, array $orderedArticleIds): bool
    {
        if (empty($orderedArticleIds)) {
            return false;
        }

        $this->db->transStart();
        foreach ($orderedArticleIds as $position => $articleId) {
            $this->db->table('articleheadline')
                ->where('articleID', (int)$articleId)
                ->where('category', $category)
                ->where('status', 1)
                ->update(['num' => $position + 1]);
        }
        $this->db->transComplete();

        return $this->db->transStatus();
    }
}
