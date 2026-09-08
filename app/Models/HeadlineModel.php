<?php

namespace App\Models;

use CodeIgniter\Model;

class HeadlineModel extends Model
{
    protected $table = 'entity_nano_headline';
    protected $primaryKey = 'record_id';
    protected $allowedFields = [
        'charlie_label',
        'ref_entity_nano_id',
        'numeric_val',
        'event_timestamp',
        'status_code'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Active headlines with article details and author
     */
    public function getActiveHeadlines(?string $label = null): array
    {
        $builder = $this->db->table('entity_nano_headline h')
            ->select('
                n.ref_entity_nano_id AS id,
                n.headline_text AS title,
                n.slug_value AS slug,
                n.summary_text AS summary,
                n.view_count AS views,
                n.status_code AS status,
                n.entity_date AS publish_date,
                n.created_at AS create_date,
                h.numeric_val AS headline_position,
                h.charlie_label AS category,
                COALESCE(ANY_VALUE(nm_thumb.meta_value), "/img/investor_placeholder.jpg") AS thumbnail,
                COALESCE(ANY_VALUE(nm_deck.meta_value), "") AS upper_deck,
                COALESCE(ANY_VALUE(nm_prem.meta_value), "0") AS is_premium,
                GROUP_CONCAT(DISTINCT a.aubert_name_a SEPARATOR ", ") AS author,
                ANY_VALUE(p.attr_identifier) AS post_by
            ')
            ->join('entity_nano n', 'n.ref_entity_nano_id = h.ref_entity_nano_id')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = n.ref_entity_acc_id', 'left')
            ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
            ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
            ->join('entity_nano_meta nm_thumb', 'nm_thumb.ref_entity_nano_id = n.ref_entity_nano_id AND nm_thumb.meta_key = "thumbnail"', 'left')
            ->join('entity_nano_meta nm_deck', 'nm_deck.ref_entity_nano_id = n.ref_entity_nano_id AND nm_deck.meta_key = "upper_deck"', 'left')
            ->join('entity_nano_meta nm_prem', 'nm_prem.ref_entity_nano_id = n.ref_entity_nano_id AND nm_prem.meta_key = "is_premium"', 'left');

        if (!empty($label) && strtolower($label) !== 'all') {
            $builder->where('LOWER(h.charlie_label)', strtolower($label));
        }

        return $builder->groupBy(['h.record_id', 'n.ref_entity_nano_id'])
            ->orderBy('h.numeric_val', 'ASC')
            ->get()->getResultArray();
    }
}
