<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class SeedHeadlineHistory extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'headline:seed-history';
    protected $description = 'Populate baseline audit trail for existing headlines into articleheadlinehistory';

    public function run(array $params)
    {
        $db = Database::connect();
        $count = $db->table('articleheadlinehistory')->countAllResults();
        if ($count > 0) {
            CLI::write("articleheadlinehistory already has {$count} records. Skipping.", 'yellow');
            return;
        }

        $headlines = $db->table('articleheadline')->get()->getResultArray();
        CLI::write("Found " . count($headlines) . " existing headlines.", 'cyan');

        $inserted = 0;
        foreach ($headlines as $h) {
            $db->table('articleheadlinehistory')->insert([
                'articleID' => $h['articleID'],
                'category'  => $h['category'],
                'action'    => ((int)$h['status'] === 1) ? 'ADD' : 'REMOVE',
                'position'  => $h['num'],
                'user'      => $h['set_by'] ?: 'admin',
                'timestamp' => $h['timestamp'] ?: date('Y-m-d H:i:s'),
                'notes'     => 'Baseline record dari headline eksisting'
            ]);
            $inserted++;
        }

        CLI::write("Successfully inserted {$inserted} baseline audit log records into articleheadlinehistory!", 'green');
    }
}
