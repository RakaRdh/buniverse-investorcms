<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameArticleCategoryPrimaryKey extends Migration
{
    public function up()
    {
        // Periksa apakah kolom 'id' ada di tabel articlecategory sebelum diubah menjadi articleCategoryID
        if ($this->db->fieldExists('id', 'articlecategory')) {
            $this->forge->modifyColumn('articlecategory', [
                'id' => [
                    'name'           => 'articleCategoryID',
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => false,
                    'auto_increment' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('articleCategoryID', 'articlecategory')) {
            $this->forge->modifyColumn('articlecategory', [
                'articleCategoryID' => [
                    'name'           => 'id',
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => false,
                    'auto_increment' => true,
                ],
            ]);
        }
    }
}
