<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticleHeadlineHistory extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('articleheadlinehistory')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'articleID' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                    'default'    => 'headline',
                ],
                'action' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false, // 'ADD', 'REMOVE', 'REORDER'
                ],
                'position' => [
                    'type'       => 'TINYINT',
                    'constraint' => 4,
                    'null'       => true,
                    'default'    => null,
                ],
                'user' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                    'default'    => 'system',
                ],
                'timestamp' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'notes' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('articleID');
            $this->forge->addKey('category');
            $this->forge->addKey('timestamp');
            $this->forge->createTable('articleheadlinehistory', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('articleheadlinehistory')) {
            $this->forge->dropTable('articleheadlinehistory', true);
        }
    }
}
