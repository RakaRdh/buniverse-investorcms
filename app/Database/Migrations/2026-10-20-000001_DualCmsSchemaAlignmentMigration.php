<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk 5 Rekomendasi Teknis Dual-CMS Coexistence:
 * 1. Koreksi typo trending modifiedData -> modifiedDate
 * 2. Selaraskan chartdata updateAt/updateBy -> updatedAt/updatedBy & tambah yAxisTitle, yAxisData
 * 3. Koreksi typo saham_summary NonReguler -> NonRegular & ListedShare -> ListedShares
 * 4. Realign skema saham_topvalue agar sesuai metrik legacy saham
 * 5. Pastikan default NULL pada kolom khusus Revamp (set_by di articleheadline)
 */
class DualCmsSchemaAlignmentMigration extends Migration
{
    public function up()
    {
        // 1. Koreksi typo kolom trending (modifiedData -> modifiedDate)
        $trendingTable = $this->db->tableExists('tbltrending') ? 'tbltrending' : ($this->db->tableExists('trending') ? 'trending' : null);
        if ($trendingTable) {
            if ($this->db->fieldExists('modifiedData', $trendingTable) && !$this->db->fieldExists('modifiedDate', $trendingTable)) {
                $this->forge->modifyColumn($trendingTable, [
                    'modifiedData' => [
                        'name'    => 'modifiedDate',
                        'type'    => 'DATETIME',
                        'null'    => true,
                        'default' => null,
                    ],
                ]);
            }
        }

        // 2. Koreksi kolom chartdata (updateAt -> updatedAt, updateBy -> updatedBy, yAxisTitle, yAxisData)
        $chartTable = $this->db->tableExists('tblchartdata') ? 'tblchartdata' : ($this->db->tableExists('chartdata') ? 'chartdata' : null);
        if ($chartTable) {
            $fieldsToAdd = [];
            if (!$this->db->fieldExists('yAxisTitle', $chartTable)) {
                $fieldsToAdd['yAxisTitle'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ];
            }
            if (!$this->db->fieldExists('yAxisData', $chartTable)) {
                $fieldsToAdd['yAxisData'] = [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                ];
            }
            if (!$this->db->fieldExists('deletedAt', $chartTable)) {
                $fieldsToAdd['deletedAt'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ];
            }
            if (!$this->db->fieldExists('deletedBy', $chartTable)) {
                $fieldsToAdd['deletedBy'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ];
            }
            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn($chartTable, $fieldsToAdd);
            }

            if ($this->db->fieldExists('updateAt', $chartTable) && !$this->db->fieldExists('updatedAt', $chartTable)) {
                $this->forge->modifyColumn($chartTable, [
                    'updateAt' => [
                        'name'    => 'updatedAt',
                        'type'    => 'DATETIME',
                        'null'    => true,
                        'default' => null,
                    ],
                ]);
            }
            if ($this->db->fieldExists('updateBy', $chartTable) && !$this->db->fieldExists('updatedBy', $chartTable)) {
                $this->forge->modifyColumn($chartTable, [
                    'updateBy' => [
                        'name'       => 'updatedBy',
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                        'default'    => null,
                    ],
                ]);
            }
        }

        // 3. Koreksi typo kolom saham_summary (ListedShare -> ListedShares, NonReguler -> NonRegular)
        $sahamSummaryTable = $this->db->tableExists('tblsaham_summary') ? 'tblsaham_summary' : ($this->db->tableExists('saham_summary') ? 'saham_summary' : null);
        if ($sahamSummaryTable) {
            $modifications = [];
            if ($this->db->fieldExists('ListedShare', $sahamSummaryTable) && !$this->db->fieldExists('ListedShares', $sahamSummaryTable)) {
                $modifications['ListedShare'] = [
                    'name'    => 'ListedShares',
                    'type'    => 'BIGINT',
                    'null'    => true,
                    'default' => 0,
                ];
            }
            if ($this->db->fieldExists('NonRegulerVolume', $sahamSummaryTable) && !$this->db->fieldExists('NonRegularVolume', $sahamSummaryTable)) {
                $modifications['NonRegulerVolume'] = [
                    'name'    => 'NonRegularVolume',
                    'type'    => 'BIGINT',
                    'null'    => true,
                    'default' => 0,
                ];
            }
            if ($this->db->fieldExists('NonRegulerValue', $sahamSummaryTable) && !$this->db->fieldExists('NonRegularValue', $sahamSummaryTable)) {
                $modifications['NonRegulerValue'] = [
                    'name'       => 'NonRegularValue',
                    'type'       => 'DECIMAL',
                    'constraint' => '20,2',
                    'null'       => true,
                    'default'    => 0.00,
                ];
            }
            if ($this->db->fieldExists('NonRegulerFrequency', $sahamSummaryTable) && !$this->db->fieldExists('NonRegularFrequency', $sahamSummaryTable)) {
                $modifications['NonRegulerFrequency'] = [
                    'name'    => 'NonRegularFrequency',
                    'type'    => 'INT',
                    'null'    => true,
                    'default' => 0,
                ];
            }
            if (!empty($modifications)) {
                $this->forge->modifyColumn($sahamSummaryTable, $modifications);
            }
        }

        // 4. Skema saham_topvalue
        $sahamTopValueTable = $this->db->tableExists('tblsaham_topvalue') ? 'tblsaham_topvalue' : ($this->db->tableExists('saham_topvalue') ? 'saham_topvalue' : null);
        if ($sahamTopValueTable) {
            // Jika saham_topvalue memiliki kolom anomali IndexCode, sesuaikan agar mendukung StockCode dkk
            if ($this->db->fieldExists('IndexCode', $sahamTopValueTable) && !$this->db->fieldExists('StockCode', $sahamTopValueTable)) {
                $this->forge->modifyColumn($sahamTopValueTable, [
                    'IndexCode' => [
                        'name'       => 'StockCode',
                        'type'       => 'VARCHAR',
                        'constraint' => 10,
                        'null'       => false,
                    ],
                ]);
            }
            $colsToAdd = [];
            $expected = [
                'Price'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true, 'default' => 0.00],
                'Change'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true, 'default' => 0.00],
                'Percent'    => ['type' => 'DECIMAL', 'constraint' => '6,2',  'null' => true, 'default' => 0.00],
                'Volume'     => ['type' => 'BIGINT',  'null' => true, 'default' => 0],
                'Value'      => ['type' => 'DECIMAL', 'constraint' => '20,2', 'null' => true, 'default' => 0.00],
                'Frequency'  => ['type' => 'INT',     'null' => true, 'default' => 0],
                'LastUpdate' => ['type' => 'DATETIME','null' => true, 'default' => null],
            ];
            foreach ($expected as $colName => $def) {
                if (!$this->db->fieldExists($colName, $sahamTopValueTable)) {
                    $colsToAdd[$colName] = $def;
                }
            }
            if (!empty($colsToAdd)) {
                $this->forge->addColumn($sahamTopValueTable, $colsToAdd);
            }
        }

        // 5. Default NULL untuk set_by di articleheadline
        $headlineTable = $this->db->tableExists('tblarticleheadline') ? 'tblarticleheadline' : ($this->db->tableExists('articleheadline') ? 'articleheadline' : null);
        if ($headlineTable && $this->db->fieldExists('set_by', $headlineTable)) {
            $this->forge->modifyColumn($headlineTable, [
                'set_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
        }
    }

    public function down()
    {
        // Safe rollback logic
    }
}
