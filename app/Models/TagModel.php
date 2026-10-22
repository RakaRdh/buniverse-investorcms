<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class TagModel
 *
 * @category Discontinued / Archival Track Record
 * @description Model representasi tabel legacy `tblTag` dan pivot `tblArticleTag`.
 * Modul ini DISCONTINUED karena seluruh operasional redaksi dan pencarian portal
 * sudah 100% beralih ke `tblKeyword` dan `tblArticleKeyword`.
 * Model ini disediakan sebagai track record aksesibilitas data historis dan skema DB.
 */
class TagModel extends Model
{
    protected $table = 'Tag';
    protected $primaryKey = 'tagID';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'label',
        'slug',
    ];
    protected $useTimestamps = false;

    /**
     * Hitung total tag di tabel legacy (Replika Smarty: Tag::totalTag)
     */
    public function totalTag(): int
    {
        return $this->countAllResults();
    }

    /**
     * Ambil tag berdasarkan slug (Replika Smarty: Tag::getTag)
     */
    public function getTagBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first();
    }

    /**
     * Ambil daftar tag berdasarkan array of IDs (Replika Smarty: Tag::getTagByArrayID)
     */
    public function getTagByArrayID(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->whereIn('tagID', $ids)->orderBy('label', 'ASC')->findAll();
    }

    /**
     * Pencarian tag dengan pagination (Replika Smarty: Tag::getTagList)
     */
    public function getTagList(string $keyword = '', int $start = 0, int $limit = 10): array
    {
        $builder = $this->builder();
        if ($keyword !== '') {
            $builder->like('label', $keyword, 'after');
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $list = $builder->orderBy('label', 'ASC')
            ->limit($limit, $start)
            ->get()
            ->getResultArray();

        return [
            'list'  => $list,
            'total' => $total,
        ];
    }

    /**
     * Menyimpan tag baru atau update (Replika Smarty: Tag::saveTag)
     */
    public function saveTag(?int $id, string $label, ?string $slug = null): int
    {
        $slug = $slug ?: url_title($label, '-', true);
        $data = [
            'label' => trim($label),
            'slug'  => $slug,
        ];

        if ($id && $this->find($id)) {
            $this->update($id, $data);
            return $id;
        }

        return (int)$this->insert($data);
    }

    /**
     * Relasi ke artikel via pivot `tblArticleTag`
     */
    public function getArticlesByTagId(int $tagId, int $limit = 20): array
    {
        return $this->db->table('Article a')
            ->select('a.articleID, a.headline, a.slug, a.articleDate, a.status')
            ->join('ArticleTag at', 'at.articleID = a.articleID')
            ->where('at.tagID', (string)$tagId)
            ->where('a.status', 'Publish')
            ->orderBy('a.articleDate', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}
