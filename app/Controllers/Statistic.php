<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;

class Statistic extends BaseController
{
    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $metrics = $this->articleModel->getDashboardMetrics();
        $kanals = $this->categoryModel->getKanalTree();
        $recentArticles = $this->articleModel->getArticlesWithRelations([], 1, 20)['data'];

        // Compute Category Distribution for Chart via SQL JOIN
        $catDistribution = $this->categoryModel->db->table('entity_charlie c')
            ->select('c.charlie_name_a AS name, COUNT(DISTINCT ncl.ref_entity_nano_id) AS count')
            ->join('entity_nano_charlie_link ncl', 'ncl.ref_entity_charlie_id = c.ref_entity_charlie_id')
            ->where('c.parent_id IS NULL')
            ->groupBy('c.ref_entity_charlie_id')
            ->orderBy('count', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $catLabels = array_column($catDistribution, 'name');
        $catSeries = array_map('intval', array_column($catDistribution, 'count'));

        return view('statistic/index', [
            'title'      => 'Audience Analytics & Traffic Statistics',
            'breadcrumb' => 'Insights / Statistic',
            'posts'      => $recentArticles,
            'kanals'     => $kanals,
            'metrics'    => [
                'totalPageviews'     => number_format($metrics['rawViews'] + 1400000),
                'rawTotalViews'      => number_format($metrics['rawViews']),
                'premiumSubscribers' => '26,400',
                'premiumArticles'    => 12,
                'publishedCount'     => $metrics['published'],
                'socialReferralPct'  => '68.4%',
                'avgReadTime'        => '3m 42s',
            ],
            'chartData'  => [
                'catLabels' => $catLabels,
                'catSeries' => $catSeries,
            ]
        ]);
    }
}
