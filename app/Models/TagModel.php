<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class TagModel
 * 
 * @deprecated LEGACY DISCONTINUED — Modul Tag tidak lagi digunakan pada CMS Investor.
 * Sistem telah dialihkan sepenuhnya menggunakan KeywordModel (tabel `keyword` dan `articlekeyword`).
 * Model ini dipertahankan murni untuk kompatibilitas data historis audit trail.
 */
class TagModel extends Model
{
    protected $table = 'tag';
    protected $primaryKey = 'tagID';
    protected $allowedFields = [
        'label',
        'slug'
    ];
    protected $useTimestamps = false;

    /**
     * Synchronize tags for article
     * Notice: tagID in articletag is varchar(60) in the production schema
     */
    public function syncArticleTags(int $articleId, array $tags): void
    {
        $this->db->table('articletag')
            ->where('articleId', $articleId)
            ->delete();

        $insertedTagIds = [];
        foreach ($tags as $label) {
            $label = trim(trim($label), " \t\n\r\0\x0B*•-");
            if (empty($label)) continue;

            $slug = url_title($label, '-', true);
            if (empty($slug)) continue;

            $tag = $this->where('slug', $slug)->orWhere('label', $label)->first();

            if (!$tag) {
                $this->insert([
                    'label' => $label,
                    'slug'  => $slug,
                ]);
                $tagId = $this->insertID();
            } else {
                $tagId = $tag['tagID'];
            }

            if (!in_array($tagId, $insertedTagIds)) {
                $this->db->table('articletag')->insert([
                    'articleId' => $articleId,
                    'tagID'     => (string)$tagId,
                ]);
                $insertedTagIds[] = $tagId;
            }
        }
    }
}
