<?php

namespace App\Controllers;

use App\Models\DataRepository;

class EmbedChart extends BaseController
{
    public function index()
    {
        $charts = DataRepository::get('embed_charts');
        return view('embed_chart/index', [
            'title' => 'Financial Embed Charts',
            'breadcrumb' => 'Insights / Embed Chart',
            'charts' => $charts
        ]);
    }

    public function save()
    {
        $charts = DataRepository::get('embed_charts');
        $title = $this->request->getPost('title');
        $symbol = $this->request->getPost('symbol') ?: 'IDX:STOCK';
        $type = $this->request->getPost('type') ?: 'Interactive Chart';
        
        $newId = count($charts) > 0 ? max(array_column($charts, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'title' => $title,
            'type' => $type,
            'symbol' => $symbol,
            'source' => 'Investor Market Data Feed',
            'author' => 'Andika M.',
            'updated' => date('d M Y H:i'),
            'status' => 'Active',
            'embed_code' => '<iframe src="https://investor.id/embed/charts/' . url_title($symbol, '-', true) . '" width="100%" height="420" frameborder="0"></iframe>'
        ];

        array_unshift($charts, $newItem);
        DataRepository::save('embed_charts', $charts);

        return redirect()->to('/embed-chart')->with('success', 'Chart embed configuration created.');
    }

    public function delete($id)
    {
        $charts = DataRepository::get('embed_charts');
        $charts = array_filter($charts, fn($c) => $c['id'] != $id);
        DataRepository::save('embed_charts', array_values($charts));

        return redirect()->to('/embed-chart')->with('success', 'Chart embed removed.');
    }
}
