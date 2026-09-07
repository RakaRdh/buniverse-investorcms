<?php

namespace App\Models;

use CodeIgniter\Model;

class ChartModel extends Model
{
    protected $table = 'chart_config';
    protected $primaryKey = 'ref_entity_chart_id';
    protected $allowedFields = [
        'chart_title',
        'x_axis_title',
        'x_axis_data',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'source_val',
        'chart_style'
    ];
    protected $useTimestamps = false;

    public function getChartsList(): array
    {
        return $this->db->table('chart_config c')
            ->select('
                c.ref_entity_chart_id AS id,
                c.chart_title AS title,
                c.chart_style AS type,
                c.x_axis_title AS symbol,
                c.source_val AS source,
                p.attr_display_name AS author,
                c.updated_at AS updated,
                "Active" AS status,
                c.x_axis_data AS embed_code
            ')
            ->join('sys_principal_entity p', 'p.ref_entity_principal_id = c.created_by', 'left')
            ->orderBy('c.ref_entity_chart_id', 'DESC')
            ->get()->getResultArray();
    }
}
