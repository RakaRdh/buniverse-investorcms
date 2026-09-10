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

    /**
     * Default Index: Direct to Template page
     */
    public function index()
    {
        return $this->template();
    }

    /**
     * Menu Template: Master default templates (Bar Vertikal, Bar Horizontal, Line, Pie)
     * Displays summary table & live interactive Chart.js previews matching each chart type.
     */
    public function template()
    {
        $templates = $this->chartModel->getTemplates();

        return view('embed_chart/template', [
            'title'      => 'Financial Embed Charts',
            'breadcrumb' => 'Embed Chart / Template',
            'templates'  => $templates,
        ]);
    }

    /**
     * Menu Custom Data: List table of all user-generated custom charts
     */
    public function custom()
    {
        $charts = $this->chartModel->getCustomChartsList();

        return view('embed_chart/custom_list', [
            'title'      => 'Custom Data Charts',
            'breadcrumb' => 'Embed Chart / Custom Data',
            'charts'     => $charts,
        ]);
    }

    /**
     * Form Create Custom Chart
     */
    public function createCustom()
    {
        return view('embed_chart/custom_form', [
            'title'      => 'Create Custom Chart',
            'breadcrumb' => 'Embed Chart / Create Custom',
            'chart'      => null,
            'isEdit'     => false,
        ]);
    }

    /**
     * Save New Custom Chart
     */
    public function saveCustom()
    {
        $title      = trim($this->request->getPost('title') ?? '');
        $style      = $this->request->getPost('chartStyle') ?? 'bar-vertical';
        $source     = trim($this->request->getPost('source') ?? 'Investor Data Stream');
        $xAxisTitle = trim($this->request->getPost('xAxisTitle') ?? 'Kategori / Periode');
        $yAxisTitle = trim($this->request->getPost('yAxisTitle') ?? 'Nilai');
        $xLabelsRaw = $this->request->getPost('xLabels') ?? '';
        $yValuesRaw = $this->request->getPost('yValues') ?? '';

        if (empty($title)) {
            return redirect()->back()->withInput()->with('error', 'Judul chart wajib diisi.');
        }

        $validStyles = ['bar-vertical', 'bar-horizontal', 'line', 'pie'];
        if (!in_array($style, $validStyles)) {
            $style = 'bar-vertical';
        }

        // Parse labels (newline or comma separated)
        $labels = $this->parseListInput($xLabelsRaw);
        if (empty($labels)) {
            $labels = ['Kuartal I', 'Kuartal II', 'Kuartal III', 'Kuartal IV'];
        }

        // Parse values (newline or comma separated)
        $values = $this->parseNumericInput($yValuesRaw);
        if (empty($values)) {
            $values = [120, 150, 190, 240];
        }

        $jsonData = json_encode([
            'labels'     => $labels,
            'values'     => $values,
            'yAxisTitle' => $yAxisTitle,
        ]);

        $sessionUser = session()->get('user_id') ?: 1;
        $now = date('Y-m-d H:i:s');

        $this->chartModel->insert([
            'chartTitle' => $title,
            'chartStyle' => $style,
            'xAxisTitle' => $xAxisTitle,
            'source'     => $source,
            'createdBy'  => $sessionUser,
            'updateBy'   => $sessionUser,
            'createdAt'  => $now,
            'updateAt'   => $now,
            'xAxisData'  => $jsonData,
        ]);

        return redirect()->to('/embed-chart/custom')->with('success', 'Custom chart baru berhasil disimpan.');
    }

    /**
     * Form Edit Custom Chart
     */
    public function editCustom($id)
    {
        $chart = $this->chartModel->find((int)$id);
        if (!$chart) {
            return redirect()->to('/embed-chart/custom')->with('error', 'Chart tidak ditemukan.');
        }

        if (($chart['source'] ?? '') === 'template') {
            return redirect()->to('/embed-chart/template')->with('error', 'Chart template bawaan sistem tidak dapat diedit.');
        }

        $chart = $this->chartModel->parseChartAxisData($chart);
        $chart['id'] = $chart['chartID'] ?? (int)$id;
        $chart['type'] = $chart['chartStyle'] ?? 'bar-vertical';

        return view('embed_chart/custom_form', [
            'title'      => 'Edit Custom Chart: ' . esc($chart['chartTitle']),
            'breadcrumb' => 'Embed Chart / Edit Custom',
            'chart'      => $chart,
            'isEdit'     => true,
        ]);
    }

    /**
     * Update Existing Custom Chart
     */
    public function updateCustom($id)
    {
        $chart = $this->chartModel->find((int)$id);
        if (!$chart) {
            return redirect()->to('/embed-chart/custom')->with('error', 'Chart tidak ditemukan.');
        }

        if (($chart['source'] ?? '') === 'template') {
            return redirect()->to('/embed-chart/template')->with('error', 'Chart template bawaan tidak dapat diubah.');
        }

        $title      = trim($this->request->getPost('title') ?? '');
        $style      = $this->request->getPost('chartStyle') ?? 'bar-vertical';
        $source     = trim($this->request->getPost('source') ?? 'Investor Data Stream');
        $xAxisTitle = trim($this->request->getPost('xAxisTitle') ?? 'Kategori / Periode');
        $yAxisTitle = trim($this->request->getPost('yAxisTitle') ?? 'Nilai');
        $xLabelsRaw = $this->request->getPost('xLabels') ?? '';
        $yValuesRaw = $this->request->getPost('yValues') ?? '';

        if (empty($title)) {
            return redirect()->back()->withInput()->with('error', 'Judul chart wajib diisi.');
        }

        $validStyles = ['bar-vertical', 'bar-horizontal', 'line', 'pie'];
        if (!in_array($style, $validStyles)) {
            $style = 'bar-vertical';
        }

        $labels = $this->parseListInput($xLabelsRaw);
        $values = $this->parseNumericInput($yValuesRaw);

        $jsonData = json_encode([
            'labels'     => $labels,
            'values'     => $values,
            'yAxisTitle' => $yAxisTitle,
        ]);

        $sessionUser = session()->get('user_id') ?: 1;
        $now = date('Y-m-d H:i:s');

        $this->chartModel->update((int)$id, [
            'chartTitle' => $title,
            'chartStyle' => $style,
            'xAxisTitle' => $xAxisTitle,
            'source'     => $source,
            'updateBy'   => $sessionUser,
            'updateAt'   => $now,
            'xAxisData'  => $jsonData,
        ]);

        return redirect()->to('/embed-chart/custom')->with('success', 'Custom chart berhasil diperbarui.');
    }

    /**
     * Legacy Save Endpoint for Backward Compatibility & Automated Tests
     */
    public function save()
    {
        $title     = $this->request->getPost('title') ?: 'New Embed Chart';
        $symbol    = $this->request->getPost('symbol') ?: 'IDX';
        $style     = $this->request->getPost('type') ?: 'bar-vertical';
        $source    = $this->request->getPost('source') ?: 'Investor Market Data';
        $dataInput = $this->request->getPost('data');
        $sessionUser = session()->get('user_id') ?: 1;

        $validStyles = ['bar-vertical', 'bar-horizontal', 'line', 'pie'];
        if (!in_array($style, $validStyles)) {
            $style = 'bar-vertical';
        }

        if (!empty($dataInput)) {
            $parsed = json_decode($dataInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $jsonData = json_encode($parsed);
            } else {
                $items = array_map('trim', explode(',', $dataInput));
                $jsonData = json_encode(array_values(array_filter($items)));
            }
        } else {
            $jsonData = json_encode([
                'labels' => ["D.I Yogyakarta", "DKI Jakarta", "Jawa Barat", "Banten", "Bali"],
                'values' => [45, 80, 65, 30, 50],
                'yAxisTitle' => 'Nilai'
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->chartModel->insert([
            'chartTitle' => $title,
            'chartStyle' => $style,
            'xAxisTitle' => $symbol,
            'source'     => $source,
            'createdBy'  => $sessionUser,
            'updateBy'   => $sessionUser,
            'createdAt'  => $now,
            'updateAt'   => $now,
            'xAxisData'  => $jsonData,
        ]);

        return redirect()->to('/embed-chart/custom')->with('success', 'Chart embed configuration created.');
    }

    /**
     * Delete Custom Chart
     */
    public function delete($id)
    {
        $chart = $this->chartModel->find((int)$id);
        if (!$chart) {
            return redirect()->to('/embed-chart/custom')->with('error', 'Chart tidak ditemukan.');
        }

        if (($chart['source'] ?? '') === 'template') {
            return redirect()->to('/embed-chart/template')->with('error', 'Chart template bawaan sistem tidak dapat dihapus.');
        }

        $this->chartModel->delete((int)$id);
        return redirect()->to('/embed-chart/custom')->with('success', 'Custom chart berhasil dihapus.');
    }

    /**
     * Helper to parse comma or newline separated text into string array
     */
    protected function parseListInput(?string $input): array
    {
        if (empty($input)) {
            return [];
        }
        $delimiters = [",", "\n", "\r"];
        $normalized = str_replace($delimiters, "\n", $input);
        $lines = explode("\n", $normalized);
        $result = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $result[] = $t;
            }
        }
        return $result;
    }

    /**
     * Helper to parse comma or newline separated text into float/int array
     */
    protected function parseNumericInput(?string $input): array
    {
        $list = $this->parseListInput($input);
        $values = [];
        foreach ($list as $item) {
            // Remove thousand separator dots/commas if any
            $cleaned = preg_replace('/[^\d\.\-]/', '', $item);
            if (is_numeric($cleaned)) {
                $values[] = (float)$cleaned == (int)$cleaned ? (int)$cleaned : (float)$cleaned;
            } else {
                $values[] = 0;
            }
        }
        return $values;
    }
}
