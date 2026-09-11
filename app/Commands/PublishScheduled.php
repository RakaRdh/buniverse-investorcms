<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishScheduled extends BaseCommand
{
    protected $group       = 'Cron';
    protected $name        = 'cron:publish-scheduled';
    protected $description = 'Publikasikan artikel berstatus Hold / terjadwal yang telah melewati waktu tayang (articleDate).';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        CLI::write("Memeriksa artikel terjadwal per waktu: {$now}...", 'yellow');

        $articles = $db->table('article')
            ->where('status', 'Hold')
            ->where('articleDate <=', $now)
            ->get()
            ->getResultArray();

        $count = count($articles);
        if ($count === 0) {
            CLI::write("Tidak ada artikel Hold yang jatuh tempo.", 'green');
            return;
        }

        $updated = 0;
        foreach ($articles as $art) {
            $db->table('article')
                ->where('articleID', $art['articleID'])
                ->update(['status' => 'Publish']);
            $updated++;
            CLI::write("  [OK] Dipublikasikan: #{$art['articleID']} - {$art['headline']}", 'cyan');
        }

        CLI::write("Berhasil mempublikasikan {$updated} artikel terjadwal.", 'green');
    }
}
