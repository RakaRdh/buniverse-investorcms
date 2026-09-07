<?php

namespace App\Models;

use CodeIgniter\Model;

class FeatureContentModel extends Model
{
    protected $table = 'content_feature';
    protected $primaryKey = 'record_id';
    protected $allowedFields = [
        'content_type',
        'content_title',
        'content_tag',
        'set_by',
        'set_date',
        'image_url',
        'link_url',
        'position_val',
        'numeric_val'
    ];
    protected $useTimestamps = false;

    public function getFeaturesList(): array
    {
        return $this->select('
                record_id AS id,
                content_title AS title,
                content_type AS type,
                content_tag AS badge,
                image_url AS image,
                link_url AS target_url,
                numeric_val AS order,
                "Active" AS status
            ')
            ->orderBy('numeric_val', 'ASC')
            ->findAll();
    }
}
