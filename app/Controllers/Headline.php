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

        // Selected target category (defaults to 'Headline')
        $targetCategory = $this->request->getGet('target_category') ?: 'Headline';
        $headlines = $this->headlineModel->getActiveHeadlines($targetCategory);

        // Filtering parameters for available posts
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
            'targetCategory' => $targetCategory,
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
        $targetCategory = $this->request->getPost('target_category') ?: 'Headline';

        $exists = $this->headlineModel
            ->where('ref_entity_nano_id', $articleId)
            ->where('charlie_label', $targetCategory)
            ->first();

        if (!$exists) {
            $maxPos = $this->headlineModel
                ->where('charlie_label', $targetCategory)
                ->selectMax('numeric_val')
                ->first()['numeric_val'] ?? 0;

            $this->headlineModel->insert([
                'charlie_label'      => $targetCategory,
                'ref_entity_nano_id' => $articleId,
                'numeric_val'        => $maxPos + 1,
                'event_timestamp'    => date('Y-m-d H:i:s'),
                'status_code'        => 1,
            ]);
            return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel berhasil ditambahkan ke antrean headline {$targetCategory}.");
        }

        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('info', "Artikel sudah terdaftar di antrean headline {$targetCategory}.");
    }

    public function remove($id)
    {
        $targetCategory = $this->request->getGet('target_category') ?: 'Headline';
        $this->headlineModel
            ->where('ref_entity_nano_id', (int)$id)
            ->where('charlie_label', $targetCategory)
            ->delete();
        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Artikel dihapus dari antrean headline {$targetCategory}.");
    }

    public function save()
    {
        $targetCategory = $this->request->getPost('target_category') ?: 'Headline';
        return redirect()->to('/headline?target_category=' . urlencode($targetCategory))->with('success', "Daftar headline kanal '{$targetCategory}' berhasil disimpan.");
    }
}
