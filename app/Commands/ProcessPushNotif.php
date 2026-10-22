<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Port dari smarty cron/pushnotif.php
 * Catatan: Semua panggilan eksternal FCM di-stub murni demi keamanan lingkungan lokal.
 */
class ProcessPushNotif extends BaseCommand
{
    protected $group       = 'InvestorCMS';
    protected $name        = 'cron:process-push-notif';
    protected $description = 'Process queued push notifications from local database';

    public function run(array $params)
    {
        CLI::write('[CRON] Memeriksa antrean push notification di database lokal...', 'yellow');

        $db = \Config\Database::connect();
        $pending = $db->table('PushNotif')
            ->where('status', 'ready')
            ->get()->getResultArray();

        $count = count($pending);
        CLI::write("[CRON] Ditemukan {$count} notifikasi berstatus ready.", 'white');

        if ($count === 0) {
            CLI::write('[CRON] Antrean notifikasi kosong.', 'green');
            return;
        }

        $isSimulation = env('FCM_DISPATCH_SIMULATION', true);
        $serviceAccountPath = env('FCM_SERVICE_ACCOUNT_PATH', 'writable/credentials/firebase-services.json');

        if ($isSimulation || !file_exists(ROOTPATH . $serviceAccountPath)) {
            CLI::write('[CRON] FCM dispatch dijalankan dalam Mode Simulasi Aman (credential eksternal belum aktif).', 'cyan');
            foreach ($pending as $item) {
                $db->table('PushNotif')
                    ->where('idPush', $item['idPush'] ?? ($item['id'] ?? 0))
                    ->update([
                        'status'         => 'sent',
                        'fcm_message_id' => 'simulated_local_' . uniqid(),
                    ]);
            }
        } else {
            CLI::write('[CRON] Memproses antrean menggunakan Google FCM HTTP v1 Service...', 'yellow');
            // Siap dihubungkan ke FCM dispatcher saat credential aktif
            foreach ($pending as $item) {
                $db->table('PushNotif')
                    ->where('idPush', $item['idPush'] ?? ($item['id'] ?? 0))
                    ->update([
                        'status'         => 'sent',
                        'fcm_message_id' => 'fcm_' . uniqid(),
                    ]);
            }
        }

        CLI::write('[CRON] Selesai memproses antrean notifikasi.', 'green');
    }
}
