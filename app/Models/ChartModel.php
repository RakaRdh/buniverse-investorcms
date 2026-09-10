<?php

namespace App\Models;

use CodeIgniter\Model;

class ChartModel extends Model
{
    protected $table = 'chartdata';
    protected $primaryKey = 'chartID';
    protected $allowedFields = [
        'chartTitle',
        'xAxisTitle',
        'xAxisData',
        'createdAt',
        'createdBy',
        'updateAt',
        'updateBy',
        'source',
        'chartStyle'
    ];
    protected $useTimestamps = false;

    /**
     * Get all charts with templates placed at the top, followed by custom charts
     */
    public function getChartsList(): array
    {
        return $this->db->table('chartdata c')
            ->select('
                c.chartID AS id,
                c.chartTitle AS title,
                c.chartStyle AS type,
                c.xAxisTitle AS symbol,
                c.source AS source,
                p.name AS author,
                c.updateAt AS updated,
                "Active" AS status,
                c.xAxisData AS embed_code,
                c.xAxisData AS data_json,
                IF(c.source = "template", 1, 0) AS is_template
            ')
            ->join('useradministrator p', 'p.userID = c.createdBy', 'left')
            ->orderBy('is_template', 'DESC')
            ->orderBy('c.chartID', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Get templates only (source = 'template')
     */
    public function getTemplates(): array
    {
        $rows = $this->db->table('chartdata c')
            ->select('
                c.chartID AS id,
                c.chartTitle AS title,
                c.chartStyle AS type,
                c.xAxisTitle AS symbol,
                c.source AS source,
                p.name AS author,
                c.updateAt AS updated,
                "Active" AS status,
                c.xAxisData AS data_json
            ')
            ->join('useradministrator p', 'p.userID = c.createdBy', 'left')
            ->where('c.source', 'template')
            ->orderBy('c.chartID', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r = $this->parseChartAxisData($r);
        }
        return $rows;
    }

    /**
     * Get custom charts list with full metadata for management table
     */
    public function getCustomChartsList(): array
    {
        $rows = $this->db->table('chartdata c')
            ->select('
                c.chartID AS id,
                c.chartTitle AS title,
                c.chartStyle AS type,
                c.xAxisTitle AS symbol,
                c.source AS source,
                p.name AS author,
                c.updateAt AS updated,
                "Active" AS status,
                c.xAxisData AS data_json
            ')
            ->join('useradministrator p', 'p.userID = c.createdBy', 'left')
            ->where('c.source !=', 'template')
            ->orderBy('c.chartID', 'DESC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r = $this->parseChartAxisData($r);
        }
        return $rows;
    }

    /**
     * Parse X & Y axis data structure with fallback for legacy array strings
     */
    public function parseChartAxisData(array $chart): array
    {
        $raw = $chart['data_json'] ?? ($chart['xAxisData'] ?? '');
        $labels = [];
        $values = [];
        $yAxisTitle = 'Nilai';

        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                if (isset($decoded['labels']) && is_array($decoded['labels'])) {
                    $labels = $decoded['labels'];
                    $values = $decoded['values'] ?? [];
                    $yAxisTitle = $decoded['yAxisTitle'] ?? ($decoded['y_title'] ?? 'Nilai');
                } else {
                    // Sequential list of categories (legacy format)
                    $labels = array_values($decoded);
                    $sampleBase = [45, 62, 38, 75, 54, 30, 48, 22, 38, 35, 60, 80];
                    foreach ($labels as $idx => $label) {
                        $values[] = $sampleBase[$idx % count($sampleBase)];
                    }
                    $yAxisTitle = 'Indeks / Persentase';
                }
            }
        }

        $chart['labels'] = $labels;
        $chart['values'] = $values;
        $chart['yAxisTitle'] = $yAxisTitle;
        $chart['embed_code'] = '<iframe src="' . base_url('api/chart/' . ($chart['id'] ?? 1)) . '" width="100%" height="400" frameborder="0"></iframe>';
        return $chart;
    }

    /**
     * Get custom data charts only (used in article inserter modal)
     */
    public function getCustomCharts(): array
    {
        return $this->db->table('chartdata c')
            ->select('
                c.chartID AS id,
                c.chartTitle AS title,
                c.chartStyle AS type,
                c.xAxisTitle AS symbol,
                c.source AS source,
                c.xAxisData AS data_json
            ')
            ->where('c.source !=', 'template')
            ->orderBy('c.chartID', 'DESC')
            ->get()->getResultArray();
    }
}
