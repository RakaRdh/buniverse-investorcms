<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table = 'asset_item_record';
    protected $primaryKey = 'ref_entity_media_id';
    protected $allowedFields = [
        'attr_short_description',
        'attr_full_description',
        'attr_resource_name',
        'attr_recorded_at',
        'attr_resource_title',
        'attr_origin_source',
        'attr_aubert_name',
        'ref_entity_principal_id'
    ];
    protected $useTimestamps = false;

    /**
     * JOIN Query: Media library with uploader info and article usage count
     */
    public function getMediaWithRelations(int $page = 1, int $perPage = 6, ?string $keyword = null): array
    {
        $builder = $this->db->table('asset_item_record m')
            ->select('
                m.ref_entity_media_id AS id,
                m.attr_resource_title AS title,
                m.attr_resource_name AS image,
                m.attr_short_description AS caption,
                m.attr_origin_source AS source,
                m.attr_recorded_at AS date,
                p.attr_identifier AS user,
                p.attr_display_name AS user_name,
                COUNT(DISTINCT mnl.ref_entity_nano_id) AS usage_count
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = m.ref_entity_principal_id', 'left')
            ->join('media_entity_nano_link mnl', 'mnl.ref_entity_media_id = m.ref_entity_media_id', 'left')
            ->groupBy('m.ref_entity_media_id')
            ->orderBy('m.attr_recorded_at', 'DESC');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('m.attr_resource_title', $keyword)
                ->orLike('m.attr_short_description', $keyword)
                ->orLike('p.attr_identifier', $keyword)
            ->groupEnd();
        }

        $countBuilder = clone $builder;
        $totalItems = count($countBuilder->get()->getResultArray());

        $offset = ($page - 1) * $perPage;
        $data = $builder->limit($perPage, $offset)->get()->getResultArray();

        return [
            'data'  => $data,
            'total' => $totalItems,
        ];
    }
}
