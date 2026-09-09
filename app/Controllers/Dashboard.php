<?php

namespace App\Controllers;

use App\Models\ArticleModel;

class Dashboard extends BaseController
{
    protected ArticleModel $articleModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
    }

    public function index()
    {
        $metrics = $this->articleModel->getDashboardMetrics();
        $topArticles = $this->articleModel->getTopArticles(5);
        $pendingArticles = $this->articleModel->getPendingArticles(5);
        $recentArticles = $this->articleModel->getArticlesWithRelations([], 1, 10)['data'];
        $chartDatasets = $this->articleModel->getPublishingChartData();

        return view('dashboard/index', [
            'title'           => 'Newsroom Overview',
            'breadcrumb'      => 'Dashboard',
            'posts'           => $recentArticles,
            'topArticles'     => $topArticles,
            'pendingArticles' => $pendingArticles,
            'metrics'         => $metrics,
            'chartDatasets'   => $chartDatasets
        ]);
    }
}
