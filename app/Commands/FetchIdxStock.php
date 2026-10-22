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
    protected $name        = 'cron:fetch-idx-stock';
    protected $description = 'Fetch and synchronize Indonesia Stock Exchange (IDX) market data';

    public function run(array $params)
    {
        CLI::write('[CRON] Memulai sinkronisasi IDX market feed...', 'yellow');

        $isSimulation = env('IDX_FEED_SIMULATION', true);

        if ($isSimulation) {
            CLI::write('[CRON] Live IDX scraping di-bypass (Mode Simulasi .env aktif). Menggunakan data lokal aman.', 'cyan');
            CLI::write('[CRON] Sinkronisasi IDX selesai.', 'green');
            return;
        }

        $db = \Config\Database::connect();

        try {
            // 1. Fetch Stock Summary
            $summaryUrl = env('IDX_SUMMARY_URL', 'https://www.idx.co.id/umbraco/Surface/TradingSummary/GetStockSummary');
            CLI::write("[CRON] Menghubungi feed IDX: {$summaryUrl}...", 'white');

            $client = \Config\Services::curlrequest([
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'application/json, text/plain, */*',
                ],
            ]);

            $response = $client->get($summaryUrl, ['http_errors' => false]);
            if ($response->getStatusCode() === 200) {
                $idxStock = json_decode($response->getBody());
                if (!empty($idxStock->data) && is_array($idxStock->data)) {
                    // Purge data older than 1 week
                    $db->table('Saham_Summary')
                        ->where('Date <', date('Y-m-d H:i:s', strtotime('-1 week')))
                        ->delete();

                    $now = date('Y-m-d H:i:s');
                    $batch = [];
                    foreach ($idxStock->data as $item) {
                        $batch[] = [
                            'Date'               => $now,
                            'StockCode'          => $item->StockCode ?? '',
                            'Previous'           => (float)($item->Previous ?? 0),
                            'OpenPrice'          => (float)($item->OpenPrice ?? 0),
                            'FirstTrade'         => (float)($item->FirstTrade ?? 0),
                            'High'               => (float)($item->High ?? 0),
                            'Low'                => (float)($item->Low ?? 0),
                            'Close'              => (float)($item->Close ?? 0),
                            'Change'             => (float)($item->Change ?? 0),
                            'Volume'             => (float)($item->Volume ?? 0),
                            'Value'              => (float)($item->Value ?? 0),
                            'Frequency'          => (int)($item->Frequency ?? 0),
                            'IndexIndividual'    => (float)($item->IndexIndividual ?? 0),
                            'Offer'              => (float)($item->Offer ?? 0),
                            'OfferVolume'        => (float)($item->OfferVolume ?? 0),
                            'Bid'                => (float)($item->Bid ?? 0),
                            'BidVolume'          => (float)($item->BidVolume ?? 0),
                            'ListedShares'       => (float)($item->ListedShares ?? 0),
                            'TradebleShares'     => (float)($item->TradebleShares ?? 0),
                            'WeightForIndex'     => (float)($item->WeightForIndex ?? 0),
                            'ForeignSell'        => (float)($item->ForeignSell ?? 0),
                            'ForeignBuy'         => (float)($item->ForeignBuy ?? 0),
                            'NonRegularVolume'   => (float)($item->NonRegularVolume ?? 0),
                            'NonRegularValue'    => (float)($item->NonRegularValue ?? 0),
                            'NonRegularFrequency'=> (int)($item->NonRegularFrequency ?? 0),
                            'Percentage'         => (float)($item->percentage ?? 0),
                        ];
                    }

                    if (!empty($batch)) {
                        $db->table('Saham_Summary')->insertBatch($batch);
                        CLI::write('[CRON] Berhasil memperbarui ' . count($batch) . ' record Saham_Summary.', 'green');
                    }
                }
            } else {
                CLI::write('[CRON] HTTP status ' . $response->getStatusCode() . ' saat mengambil feed IDX Summary.', 'yellow');
            }
        } catch (\Throwable $e) {
            CLI::write('[CRON] Error sinkronisasi feed IDX: ' . $e->getMessage(), 'red');
            log_message('error', '[FetchIdxStock] ' . $e->getMessage());
        }

        CLI::write('[CRON] Sinkronisasi IDX selesai.', 'green');
    }
}
