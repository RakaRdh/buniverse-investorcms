<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class TrendingExcludeModel
 *
 * @category Discontinued / Archival Track Record
 * @description Model representasi tabel legacy `tblTrendingExclude`.
 * Digunakan untuk mengecualikan artikel tertentu dari kalkulasi agregasi trending topic.
 * Modul berstatus DISCONTINUED dan disediakan untuk track record skema database.
 */
class TrendingExcludeModel extends Model
{
    protected $table = 'TrendingExclude';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'articleID',
        'trendingID',
    ];
    protected $useTimestamps = false;

    /**
     * Mengambil daftar artikel yang dikecualikan untuk trending ID tertentu
     */
    public function getExcludes(int $trendingId): array
    {
        return $this->where('trendingID', $trendingId)->findAll();
    }

    /**
     * Menambahkan pengecualian artikel
     */
    public function addExclude(int $articleId, int $trendingId): bool
    {
        $existing = $this->where('articleID', $articleId)
            ->where('trendingID', $trendingId)
            ->first();

        if ($existing) {
            return true;
        }

        return (bool)$this->insert([
            'articleID'  => $articleId,
            'trendingID' => $trendingId,
        ]);
    }

    /**
     * Menghapus pengecualian artikel
     */
    public function removeExclude(int $articleId, int $trendingId): bool
    {
        return (bool)$this->where('articleID', $articleId)
            ->where('trendingID', $trendingId)
            ->delete();
    }
}
