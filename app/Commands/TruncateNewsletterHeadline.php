<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TruncateNewsletterHeadline extends BaseCommand
{
    protected $group       = 'Cron';
    protected $name        = 'cron:truncate-newsletter';
    protected $description = 'Mereset (truncate) slot headline kategori newsletter pada siklus berkala (08:00, 14:00, 20:00 WIB).';

    /**
     * Panduan Crontab Server Produksi (Linux):
     * 0 8,14,20 * * * cd /path/to/InvestorCMS && php spark cron:truncate-newsletter > /dev/null 2>&1
     *
     * Catatan: Pemicu background cron di lingkungan Windows lokal diletakkan sebagai standalone command CLI
     * untuk dieksekusi secara terisolasi tanpa memicu dependensi daemon OS.
     */
    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        CLI::write("Memeriksa headline kategori 'newsletter' per waktu: {$now}...", 'yellow');

        $activeNewsletters = $db->table('articleheadline')
            ->where('category', 'newsletter')
            ->where('status', 1)
            ->get()
            ->getResultArray();

        $count = count($activeNewsletters);
        if ($count === 0) {
            CLI::write("Tidak ada headline newsletter aktif yang perlu di-truncate.", 'green');
            return;
        }

        // Soft truncate: update status = 0 untuk arsip paritas legacy
        $db->table('articleheadline')
            ->where('category', 'newsletter')
            ->where('status', 1)
            ->update([
                'status'    => 0,
                'set_by'    => 'CRON_SYSTEM',
                'timestamp' => $now,
            ]);

        // Catat ke audit log articleheadlinehistory jika tabel tersedia
        if ($db->tableExists('articleheadlinehistory')) {
            foreach ($activeNewsletters as $item) {
                $db->table('articleheadlinehistory')->insert([
                    'articleID'  => $item['articleID'],
                    'category'   => 'newsletter',
                    'action'     => 'TRUNCATE',
                    'position'   => $item['num'] ?? null,
                    'user'       => 'CRON_SYSTEM',
                    'notes'      => 'Otomatis di-truncate oleh sistem cron scheduler (siklus newsletter)',
                    'timestamp'  => $now,
                ]);
            }
        }

        CLI::write("Berhasil mereset {$count} slot headline newsletter aktif.", 'green');
    }
}
