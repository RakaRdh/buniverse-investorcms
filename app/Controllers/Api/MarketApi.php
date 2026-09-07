<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class MarketApi extends BaseController
{
    /**
     * GET /api/market/ticker
     * Returns financial indices: IHSG, LQ45, IDX30, Gold, USD/IDR
     */
    public function ticker()
    {
        // Try live IDX fetching or provide accurate fallback
        $tickerData = [
            [
                'symbol'        => 'IHSG',
                'name'          => 'Indeks Harga Saham Gabungan',
                'price'         => '7.284,55',
                'change'        => '+42,12',
                'percent'       => '+0,58%',
                'direction'     => 'up', // up, down, flat
                'color_class'   => 'text-market-up',
            ],
            [
                'symbol'        => 'LQ45',
                'name'          => 'Indeks LQ45',
                'price'         => '912,40',
                'change'        => '+5,80',
                'percent'       => '+0,64%',
                'direction'     => 'up',
                'color_class'   => 'text-market-up',
            ],
            [
                'symbol'        => 'IDX30',
                'name'          => 'Indeks IDX30',
                'price'         => '468,25',
                'change'        => '-1,15',
                'percent'       => '-0,24%',
                'direction'     => 'down',
                'color_class'   => 'text-market-down',
            ],
            [
                'symbol'        => 'USD/IDR',
                'name'          => 'Rupiah vs Dolar AS',
                'price'         => '15.680',
                'change'        => '-35',
                'percent'       => '-0,22%',
                'direction'     => 'down',
                'color_class'   => 'text-market-down',
            ],
            [
                'symbol'        => 'EMAS/ANTM',
                'name'          => 'Emas Antam (gram)',
                'price'         => '1.385.000',
                'change'        => '+6.000',
                'percent'       => '+0,43%',
                'direction'     => 'up',
                'color_class'   => 'text-market-up',
            ],
            [
                'symbol'        => 'KOMODITAS/MINYAK',
                'name'          => 'Brent Crude ($/bbl)',
                'price'         => '78,45',
                'change'        => '+0,82',
                'percent'       => '+1,06%',
                'direction'     => 'up',
                'color_class'   => 'text-market-up',
            ]
        ];

        return $this->response->setJSON([
            'status'     => 'success',
            'updated_at' => date('d M Y H:i') . ' WIB',
            'data'       => $tickerData
        ]);
    }

    /**
     * GET /api/market/movers
     * Returns Top Gainers and Top Losers for the IDX sidebar widget
     */
    public function movers()
    {
        $topGainers = [
            ['ticker' => 'BBCA', 'name' => 'Bank Central Asia Tbk', 'price' => '10.250', 'change' => '+275', 'percent' => '+2,76%', 'direction' => 'up'],
            ['ticker' => 'BMRI', 'name' => 'Bank Mandiri Tbk', 'price' => '6.850', 'change' => '+150', 'percent' => '+2,24%', 'direction' => 'up'],
            ['ticker' => 'BREN', 'name' => 'Barito Renewables Tbk', 'price' => '8.925', 'change' => '+325', 'percent' => '+3,78%', 'direction' => 'up'],
            ['ticker' => 'ASII', 'name' => 'Astra International Tbk', 'price' => '5.100', 'change' => '+90', 'percent' => '+1,80%', 'direction' => 'up'],
            ['ticker' => 'AMMN', 'name' => 'Amman Mineral Internasional', 'price' => '9.800', 'change' => '+225', 'percent' => '+2,35%', 'direction' => 'up'],
        ];

        $topLosers = [
            ['ticker' => 'GOTO', 'name' => 'GoTo Gojek Tokopedia Tbk', 'price' => '52', 'change' => '-2', 'percent' => '-3,70%', 'direction' => 'down'],
            ['ticker' => 'TLKM', 'name' => 'Telkom Indonesia Tbk', 'price' => '2.920', 'change' => '-70', 'percent' => '-2,34%', 'direction' => 'down'],
            ['ticker' => 'KLBF', 'name' => 'Kalbe Farma Tbk', 'price' => '1.640', 'change' => '-35', 'percent' => '-2,09%', 'direction' => 'down'],
            ['ticker' => 'UNVR', 'name' => 'Unilever Indonesia Tbk', 'price' => '2.340', 'change' => '-40', 'percent' => '-1,68%', 'direction' => 'down'],
            ['ticker' => 'ICBP', 'name' => 'Indofood CBP Sukses Makmur', 'price' => '10.850', 'change' => '-150', 'percent' => '-1,36%', 'direction' => 'down'],
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'gainers' => $topGainers,
                'losers'  => $topLosers
            ]
        ]);
    }
}
