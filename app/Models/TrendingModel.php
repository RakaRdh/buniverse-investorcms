<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class TrendingModel
 *
 * @category Discontinued / Archival Track Record
 * @description Model representasi tabel legacy `tblTrending`.
 * Modul ini berstatus DISCONTINUED dalam alur editorial aktif harian, karena redaksi
 * telah beralih 100% menggunakan kurasi Headline kategori 'trending' (`tblArticleHeadline`).
 * Disediakan sebagai track record & pemeliharaan skema data historis.
 */
class TrendingModel extends Model
{
    protected $table = 'Trending';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'label',
        'slug',
        'tag',
        'publishDate',
        'minArticlePublishDate',
        'createDate',
        'modifiedDate',
        'status',
    ];
    protected $useTimestamps = false;

    /**
     * Mengambil daftar trending topics dengan pagination (Replika Smarty: Article::getTrending)
     */
    public function getTrending(int $trendingId = 0, int $start = 0, int $limit = 10): array
    {
        $builder = $this->builder();

        if ($trendingId > 0) {
            $builder->where('id', $trendingId);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $list = $builder->orderBy('publishDate', 'DESC')
            ->limit($limit, $start)
            ->get()
            ->getResultArray();

        return [
            'trending' => $list,
            'total'    => $total,
        ];
    }

    /**
     * Menyimpan atau memperbarui data trending topic (Replika Smarty: Article::saveTrendingTopic)
     */
    public function saveTrendingTopic(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $record = [
            'label'                 => $data['label'] ?? '',
            'slug'                  => $data['slug'] ?? url_title($data['label'] ?? '', '-', true),
            'tag'                   => is_array($data['tag'] ?? null) ? json_encode($data['tag']) : ($data['tag'] ?? ''),
            'publishDate'           => $data['publishDate'] ?? date('Y-m-d'),
            'minArticlePublishDate' => $data['minArticlePublishDate'] ?? date('Y-m-d', strtotime('-30 days')),
            'modifiedDate'          => $now,
            'status'                => isset($data['status']) ? (string)$data['status'] : '1',
        ];

        if ($id && $this->find($id)) {
            return (bool)$this->update($id, $record);
        }

        $record['createDate'] = $now;
        return (bool)$this->insert($record);
    }

    /**
     * Menghapus record trending topic
     */
    public function deleteTrending(int $id): bool
    {
        return (bool)$this->delete($id);
    }

    /**
     * Mengambil artikel yang terhubung dengan tag pada rentang tanggal trending
     */
    public function getArticlesByTrendingTag(string $tagJson, ?string $minDate = null, int $limit = 10): array
    {
        $tagIds = json_decode($tagJson, true);
        if (empty($tagIds) || !is_array($tagIds)) {
            return [];
        }

        $builder = $this->db->table('Article a')
            ->select('a.articleID, a.headline, a.slug, a.articleDate, a.viewed, a.status')
            ->join('ArticleKeyword ak', 'ak.articleID = a.articleID')
            ->whereIn('ak.keywordID', $tagIds)
            ->where('a.status', 'Publish');

        if ($minDate) {
            $builder->where('a.articleDate >=', $minDate);
        }

        return $builder->groupBy('a.articleID')
            ->orderBy('a.viewed', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
