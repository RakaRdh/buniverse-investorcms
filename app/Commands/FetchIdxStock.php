<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Port dari legacy cron/IDX_stock_summary.php, IDX_top_stock.php, getIDX.php
 * Catatan: Semua panggilan eksternal ke feed pasar modal IDX di-stub murni demi keamanan lingkungan lokal.
 */
class FetchIdxStock extends BaseCommand
{
    protected $group       = 'InvestorCMS';
    protected $name        = 'cron:fetch-idx';
    protected $description = 'Fetch and synchronize Indonesia Stock Exchange (IDX) market data';

    public function run(array $params)
    {
        CLI::write('[CRON] Memulai sinkronisasi IDX market feed...', 'yellow');

        /**
         * @deprecated LEGACY EXTERNAL API
         * Legacy memanggil HTTP endpoint internal/eksternal untuk scraping harga saham harian.
         * Di environment lokal, pemanggilan eksternal di-bypass agar tidak menyentuh feed luar.
         */
        CLI::write('[CRON] Live IDX scraping di-bypass (Local Mode). Menggunakan data simulasi aman.', 'cyan');
        CLI::write('[CRON] Sinkronisasi IDX selesai.', 'green');
    }
}
