<?php

namespace App\Controllers;

use App\Models\ChartModel;

class EmbedChart extends BaseController
{
    protected ChartModel $chartModel;

    public function __construct()
    {
        $this->chartModel = new ChartModel();
    }

    public function index()
    {
        $charts = $this->chartModel->getChartsList();

        return view('embed_chart/index', [
            'title'      => 'Financial Embed Charts',
            'breadcrumb' => 'Insights / Embed Chart',
            'charts'     => $charts
        ]);
    }

    public function save()
    {
        $title = $this->request->getPost('title');
        $symbol = $this->request->getPost('symbol') ?: 'IDX:STOCK';
        $type = $this->request->getPost('type') ?: 'Interactive Chart';
        $sessionUser = session()->get('user_id') ?: 1;

        $this->chartModel->insert([
            'chart_title'  => $title,
            'chart_style'  => $type,
            'x_axis_title' => $symbol,
            'source_val'   => 'Investor Market Data Feed',
            'created_by'   => $sessionUser,
            'updated_by'   => $sessionUser,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
            'x_axis_data'  => '<iframe src="https://investor.id/embed/charts/' . url_title($symbol, '-', true) . '" width="100%" height="420" frameborder="0"></iframe>'
        ]);

        return redirect()->to('/embed-chart')->with('success', 'Chart embed configuration created.');
    }

    public function delete($id)
    {
        $this->chartModel->delete((int)$id);
        return redirect()->to('/embed-chart')->with('success', 'Chart embed removed.');
    }
}
