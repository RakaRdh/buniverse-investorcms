<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiLogsAndArticlePoints extends Migration
{
    public function up()
    {
        // 1. Tabel article points / entity_nano_points (Pure standalone, NO FK)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ref_entity_nano_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'ID artikel entity_nano tanpa FK constraint',
            ],
            'point_text' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'sort_order' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'default'    => 1,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ref_entity_nano_id', false, false, 'idx_nano_points_article');
        $this->forge->createTable('article_points', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);

        // 2. Tabel ai_generated_logs (Pure standalone, NO FK)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ref_entity_nano_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'ID artikel entity_nano bila sudah tersimpan, tanpa FK',
            ],
            'ref_entity_principal_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
                'comment'    => 'ID user sys_principal_entity pelaksana AI, tanpa FK',
            ],
            'action_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'title, summary, tags, points, scrape',
            ],
            'provider_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'gemini',
            ],
            'model_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'input_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'input_body_snapshot' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'generated_result' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'was_accepted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'comment'    => 'NULL = unconfirmed, 1 = accepted, 0 = rejected',
            ],
            'latency_ms' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'error_message' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ref_entity_nano_id', false, false, 'idx_ai_logs_nano');
        $this->forge->addKey('ref_entity_principal_id', false, false, 'idx_ai_logs_principal');
        $this->forge->addKey('action_type', false, false, 'idx_ai_logs_action');
        $this->forge->addKey('created_at', false, false, 'idx_ai_logs_created');
        $this->forge->createTable('ai_generated_logs', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('article_points', true);
        $this->forge->dropTable('ai_generated_logs', true);
    }
}
