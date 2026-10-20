<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Port dari smarty cron/deletelog.php
 * Membersihkan log audit trail lama dari tabel EditorLog
 */
class CleanupLogs extends BaseCommand
{
    protected $group       = 'InvestorCMS';
    protected $name        = 'cron:cleanup-logs';
    protected $description = 'Clean up old editor logs older than 90 days';

    public function run(array $params)
    {
        CLI::write('[CRON] Membersihkan log editorial lama (>90 hari)...', 'yellow');

        $db = \Config\Database::connect();
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-90 days'));

        $deleted = $db->table('EditorLog')
            ->where('timestamp <', $cutoffDate)
            ->delete();

        CLI::write('[CRON] Pembersihan log selesai.', 'green');
    }
}
