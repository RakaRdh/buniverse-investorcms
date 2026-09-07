<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'entity_charlie';
    protected $primaryKey = 'ref_entity_charlie_id';
    protected $allowedFields = [
        'charlie_name_a',
        'field_label_a',
        'parent_id',
        'status_code',
        'numeric_val'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Get Kanal Tree (Root and Sub-Kanals with article counts and views)
     */
    public function getKanalTree(): array
    {
        $parents = $this->db->table('entity_charlie c')
            ->select('
                c.ref_entity_charlie_id AS id,
                c.charlie_name_a AS name,
                c.field_label_a AS slug,
                c.status_code,
                c.numeric_val,
                COUNT(DISTINCT ncl.ref_entity_nano_id) AS article_count,
                SUM(COALESCE(n.view_count, 0)) AS total_views
            ')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_charlie_id = c.ref_entity_charlie_id', 'left')
            ->join('entity_nano n', 'n.ref_entity_nano_id = ncl.ref_entity_nano_id', 'left')
            ->where('c.parent_id IS NULL')
            ->groupBy('c.ref_entity_charlie_id')
            ->orderBy('c.numeric_val', 'ASC')
            ->get()->getResultArray();

        foreach ($parents as &$p) {
            $p['sub_kanals'] = $this->db->table('entity_charlie c')
                ->select('
                    c.ref_entity_charlie_id AS id,
                    c.charlie_name_a AS name,
                    c.field_label_a AS slug,
                    c.status_code,
                    COUNT(DISTINCT ncl.ref_entity_nano_id) AS article_count,
                    SUM(COALESCE(n.view_count, 0)) AS total_views
                ')
                ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_charlie_id = c.ref_entity_charlie_id', 'left')
                ->join('entity_nano n', 'n.ref_entity_nano_id = ncl.ref_entity_nano_id', 'left')
                ->where('c.parent_id', $p['id'])
                ->groupBy('c.ref_entity_charlie_id')
                ->orderBy('c.numeric_val', 'ASC')
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
        return $this->db->table('entity_charlie sub')
            ->select('
                sub.ref_entity_charlie_id AS id,
                sub.charlie_name_a AS name,
                sub.field_label_a AS slug,
                parent.charlie_name_a AS parent_name,
                parent.field_label_a AS parent_slug,
                COUNT(DISTINCT ncl.ref_entity_nano_id) AS article_count,
                SUM(COALESCE(n.view_count, 0)) AS total_views
            ')
            ->join('entity_charlie parent', 'parent.ref_entity_charlie_id = sub.parent_id')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_charlie_id = sub.ref_entity_charlie_id', 'left')
            ->join('entity_nano n', 'n.ref_entity_nano_id = ncl.ref_entity_nano_id', 'left')
            ->where('sub.parent_id IS NOT NULL')
            ->groupBy('sub.ref_entity_charlie_id')
            ->orderBy('parent.numeric_val', 'ASC')
            ->orderBy('sub.numeric_val', 'ASC')
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
     * Sync single row to backup table entity_charlie_backup
     */
    public function syncBackup(int $id): void
    {
        $row = $this->find($id);
        if ($row) {
            $this->db->table('entity_charlie_backup')->replace($row);
        }
    }
}
