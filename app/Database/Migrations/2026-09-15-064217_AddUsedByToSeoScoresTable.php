<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsedByToSeoScoresTable extends Migration
{
    public function up()
    {
        $fields = [
            'used_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'scored_by'
            ],
        ];
        $this->forge->addColumn('seo_scores', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('seo_scores', 'used_by');
    }
}
