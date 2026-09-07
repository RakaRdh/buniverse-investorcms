<?php

namespace App\Models;

use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table = 'entity_teta';
    protected $primaryKey = 'ref_entity_teta_id';
    protected $allowedFields = [
        'field_label_a',
        'slug_value'
    ];
    protected $useTimestamps = false;

    /**
     * Synchronize tags for article
     * Notice: ref_entity_teta_id in entity_nano_teta_link is varchar(60) in the original schema
     */
    public function syncArticleTags(int $nanoId, array $tags): void
    {
        $this->db->table('entity_nano_teta_link')
            ->where('ref_entity_nano_id', $nanoId)
            ->delete();

        $insertedTagIds = [];
        foreach ($tags as $label) {
            $label = trim(trim($label), " \t\n\r\0\x0B*•-");
            if (empty($label)) continue;

            $slug = url_title($label, '-', true);
            if (empty($slug)) continue;

            $tag = $this->where('slug_value', $slug)->orWhere('field_label_a', $label)->first();

            if (!$tag) {
                $this->insert([
                    'field_label_a' => $label,
                    'slug_value'    => $slug,
                ]);
                $tagId = $this->insertID();
            } else {
                $tagId = $tag['ref_entity_teta_id'];
            }

            if (!in_array($tagId, $insertedTagIds)) {
                $this->db->table('entity_nano_teta_link')->insert([
                    'ref_entity_nano_id' => $nanoId,
                    'ref_entity_teta_id' => (string)$tagId,
                ]);
                $insertedTagIds[] = $tagId;
            }
        }
    }
}
