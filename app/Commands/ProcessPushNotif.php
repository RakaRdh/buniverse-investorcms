<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Port dari legacy cron/pushnotif.php
 * Catatan: Semua panggilan eksternal FCM di-stub murni demi keamanan lingkungan lokal.
 */
class ProcessPushNotif extends BaseCommand
{
    protected $group       = 'InvestorCMS';
    protected $name        = 'cron:process-push';
    protected $description = 'Process queued push notifications from local database';

    public function run(array $params)
    {
        CLI::write('[CRON] Memeriksa antrean push notification di database lokal...', 'yellow');

        $db = \Config\Database::connect();
        $pending = $db->table('pushnotif')
            ->where('status', 'ready')
            ->get()->getResultArray();

        $count = count($pending);
        CLI::write("[CRON] Ditemukan {$count} notifikasi berstatus ready.", 'white');

        /**
         * @deprecated LEGACY EXTERNAL API
         * Legacy memanggil Firebase Cloud Messaging (FCM) legacy HTTP server key.
         * Di environment lokal CI4, pemanggilan eksternal di-bypass demi zero production risk.
         */
        foreach ($pending as $item) {
            $db->table('pushnotif')
                ->where('id', $item['id'])
                ->update([
                    'status'         => 'sent',
                    'fcm_message_id' => 'mock_local_' . uniqid(),
                ]);
        }

        CLI::write('[CRON] Selesai memproses antrean notifikasi.', 'green');
    }
}
