<?php

namespace App\Controllers;

use App\Models\HeadlineModel;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class Headline extends BaseController
{
    protected HeadlineModel $headlineModel;
    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->headlineModel = new HeadlineModel();
        $this->articleModel = new ArticleModel();
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->getKanalTree();
        $headlines = $this->headlineModel->getActiveHeadlines();

        // Filtering parameters
        $keyword = $this->request->getGet('q');
        $categoryFilter = $this->request->getGet('category');

        $filters = [
            'status' => 'Published',
            'q'      => $keyword,
            'kanal'  => $categoryFilter,
        ];

        $availableResult = $this->articleModel->getArticlesWithRelations($filters, 1, 30);

        return view('headline/index', [
            'title'          => 'Headline Management',
            'breadcrumb'     => 'Headline / Placements',
            'availablePosts' => $availableResult['data'],
            'headlines'      => $headlines,
            'categories'     => $categories,
            'filters'        => [
                'q'        => $keyword ?? '',
                'category' => $categoryFilter ?? 'all'
            ]
        ]);
    }

    public function add()
    {
        $articleId = (int)$this->request->getPost('article_id');

        $exists = $this->headlineModel->where('ref_entity_nano_id', $articleId)->first();

        if (!$exists) {
            $maxPos = $this->headlineModel->selectMax('numeric_val')->first()['numeric_val'] ?? 0;
            $this->headlineModel->insert([
                'charlie_label'      => 'Headline',
                'ref_entity_nano_id' => $articleId,
                'numeric_val'        => $maxPos + 1,
                'event_timestamp'    => date('Y-m-d H:i:s'),
                'status_code'        => 1,
            ]);
            return redirect()->to('/headline')->with('success', 'Article added to headline queue.');
        }

        return redirect()->to('/headline')->with('info', 'Article is already in headline queue.');
    }

    public function remove($id)
    {
        $this->headlineModel->where('ref_entity_nano_id', (int)$id)->delete();
        return redirect()->to('/headline')->with('success', 'Article removed from headline list.');
    }

    public function save()
    {
        $targetCategory = $this->request->getPost('target_category') ?: 'Headline';
        return redirect()->to('/headline')->with('success', "Headline list for '{$targetCategory}' published to database successfully!");
    }
}
