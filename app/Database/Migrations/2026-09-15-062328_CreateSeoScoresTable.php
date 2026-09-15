<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSeoScoresTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'article_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'final_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'rule_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'ai_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'score_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'details' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'ai_feedback' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'recommendations' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'model_used' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'scored_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('article_id');
        $this->forge->createTable('seo_scores', true);
    }

    public function down()
    {
        $this->forge->dropTable('seo_scores', true);
    }
}
