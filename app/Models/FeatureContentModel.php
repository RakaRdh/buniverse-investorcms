<?php

namespace App\Models;

use CodeIgniter\Model;

class FeatureContentModel extends Model
{
    protected $table = 'FeatureContent';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'type',
        'title',
        'tag',
        'setby',
        'setdate',
        'imageUrl',
        'linkUrl',
        'position',
        'num'
    ];
    protected $useTimestamps = false;

    public function getFeaturesList(): array
    {
        return $this->select('
                id AS id,
                title AS title,
                type AS type,
                tag AS badge,
                imageUrl AS image,
                linkUrl AS target_url,
                num AS order,
                "Active" AS status
            ')
            ->orderBy('num', 'ASC')
            ->findAll();
    }
}
