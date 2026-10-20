<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'Category';
    protected $primaryKey = 'categoryID';
    protected $allowedFields = [
        'categoryName',
        'label',
        'parent',
        'status',
        'num'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Get Kanal Tree (Root and Sub-Kanals with article counts and views)
     */
    public function getKanalTree(): array
    {
        $parents = $this->db->table('Category c')
            ->select('
                c.categoryID AS id,
                c.label AS name,
                c.categoryName AS slug,
                c.status AS status_code,
                c.num AS numeric_val,
                COUNT(DISTINCT ncl.articleID) AS article_count,
                SUM(COALESCE(n.viewed, 0)) AS total_views
            ')
            ->join('ArticleCategory ncl', 'ncl.categoryID = c.categoryID', 'left')
            ->join('Article n', 'n.articleID = ncl.articleID', 'left')
            ->groupStart()
                ->where('c.parent IS NULL')
                ->orWhere('c.parent', 0)
                ->orWhere('c.parent', '')
            ->groupEnd()
            ->groupBy('c.categoryID')
            ->orderBy('c.num', 'ASC')
            ->get()->getResultArray();

        foreach ($parents as &$p) {
            $p['sub_kanals'] = $this->db->table('Category c')
                ->select('
                    c.categoryID AS id,
                    c.label AS name,
                    c.categoryName AS slug,
                    c.status AS status_code,
                    COUNT(DISTINCT ncl.articleID) AS article_count,
                    SUM(COALESCE(n.viewed, 0)) AS total_views
                ')
                ->join('ArticleCategory ncl', 'ncl.categoryID = c.categoryID', 'left')
                ->join('Article n', 'n.articleID = ncl.articleID', 'left')
                ->where('c.parent', $p['id'])
                ->groupBy('c.categoryID')
                ->orderBy('c.num', 'ASC')
                ->get()->getResultArray();
            $p['sub_count'] = count($p['sub_kanals']);
        }

        return $parents;
    }

    /**
     * Flattened sub-kanals list with parent info
     */
    public function getSubKanalsList(): array
    {
        return $this->db->table('Category sub')
            ->select('
                sub.categoryID AS id,
                sub.label AS name,
                sub.categoryName AS slug,
                parent.label AS parent_name,
                parent.categoryName AS parent_slug,
                COUNT(DISTINCT ncl.articleID) AS article_count,
                SUM(COALESCE(n.viewed, 0)) AS total_views
            ')
            ->join('Category parent', 'parent.categoryID = sub.parent')
            ->join('ArticleCategory ncl', 'ncl.categoryID = sub.categoryID', 'left')
            ->join('Article n', 'n.articleID = ncl.articleID', 'left')
            ->where('sub.parent IS NOT NULL')
            ->groupBy('sub.categoryID')
            ->orderBy('parent.num', 'ASC')
            ->orderBy('sub.num', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Kanal Performance Statistics via JOIN & Aggregates
     */
    public function getKanalPerformance(): array
    {
        $parents = $this->getKanalTree();
        $perf = [];

        foreach ($parents as $k) {
            $perf[] = [
                'name'            => $k['name'],
                'slug'            => $k['slug'],
                'sub_count'       => $k['sub_count'],
                'total_articles'  => (int)$k['article_count'],
                'published_count' => (int)$k['article_count'],
                'total_views'     => (int)$k['total_views'],
                'avg_views'       => $k['article_count'] > 0 ? (int)round($k['total_views'] / $k['article_count']) : 0,
                'premium_count'   => 0,
            ];
        }

        usort($perf, fn($a, $b) => $b['total_views'] <=> $a['total_views']);
        return $perf;
    }

    /**
     * Sync single row to backup table category_backup
     */
    public function syncBackup(int $id): void
    {
        $row = $this->find($id);
        if ($row) {
            $this->db->table('CategoryBackup')->replace($row);
        }
    }
}
