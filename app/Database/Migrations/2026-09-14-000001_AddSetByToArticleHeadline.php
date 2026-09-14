<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSetByToArticleHeadline extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('articleheadline') && !$this->db->fieldExists('set_by', 'articleheadline')) {
            $this->forge->addColumn('articleheadline', [
                'set_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'status',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('articleheadline') && $this->db->fieldExists('set_by', 'articleheadline')) {
            $this->forge->dropColumn('articleheadline', 'set_by');
        }
    }
}
