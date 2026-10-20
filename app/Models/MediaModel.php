<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table = 'Media';
    protected $primaryKey = 'mediaID';
    protected $allowedFields = [
        'caption',
        'description',
        'filename',
        'timestamp',
        'mediaTitle',
        'source',
        'author',
        'userID'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Media library with uploader info and article usage count
     */
    public function getMediaWithRelations(int $page = 1, int $perPage = 6, ?string $keyword = null): array
    {
        $builder = $this->db->table('Media m')
            ->select('
                m.mediaID AS id,
                m.mediaTitle AS title,
                m.filename AS image,
                m.caption AS caption,
                COALESCE(NULLIF(TRIM(m.description), \'\'), m.caption, m.mediaTitle, \'\') AS description,
                m.source AS source,
                m.timestamp AS date,
                p.username AS user,
                p.name AS user_name,
                COUNT(DISTINCT mnl.articleID) AS usage_count
            ', false)
            ->join('UserAdministrator p', 'p.userID = m.userID', 'left')
            ->join('MediaArticle mnl', 'mnl.mediaID = m.mediaID', 'left')
            ->groupBy('m.mediaID')
            ->orderBy('m.timestamp', 'DESC');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('m.mediaTitle', $keyword)
                ->orLike('m.caption', $keyword)
                ->orLike('p.username', $keyword)
            ->groupEnd();
        }

        $countBuilder = clone $builder;
        $totalItems = count($countBuilder->get()->getResultArray());

        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)->get()->getResultArray();

        foreach ($data as &$row) {
            $row['image'] = media_url($row['image'] ?? null);
        }
        unset($row);

        return [
            'data'  => $data,
            'total' => $totalItems,
        ];
    }

    /**
     * Replika legacy: Media::GetMediaToArticle()
     * Mengambil daftar artikel yang menggunakan foto/media ini
     * Tabel: mediaarticle (R), article (R)
     */
    public function getArticlesUsingMedia(int $mediaId): array
    {
        return $this->db->table('MediaArticle ma')
            ->select('
                a.articleID AS id,
                a.headline AS title,
                a.slug AS slug,
                a.status AS status,
                a.articleDate AS publish_date
            ')
            ->join('Article a', 'a.articleID = ma.articleID')
            ->where('ma.mediaID', $mediaId)
            ->orderBy('a.articleDate', 'DESC')
            ->get()->getResultArray();
    }
}
