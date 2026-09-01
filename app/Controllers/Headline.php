<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Headline extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts');
        $categories = DataRepository::get('categories');
        $headlines = DataRepository::get('active_headlines') ?? [];

        if (empty($headlines)) {
            // Default 3 headlines from published articles
            $published = array_values(array_filter($posts, fn($p) => $p['status'] === 'Published'));
            $headlines = array_slice($published, 0, 3);
            DataRepository::save('active_headlines', $headlines);
        }

        // Filtering parameters
        $keyword = $this->request->getGet('q');
        $categoryFilter = $this->request->getGet('category');

        $availablePosts = array_values(array_filter($posts, fn($p) => $p['status'] === 'Published'));

        if ($keyword) {
            $availablePosts = array_filter($availablePosts, fn($p) => 
                stripos($p['title'], $keyword) !== false || 
                stripos($p['author'], $keyword) !== false
            );
        }

        if ($categoryFilter && $categoryFilter !== 'all') {
            $availablePosts = array_filter($availablePosts, fn($p) => $p['category'] === $categoryFilter);
        }

        return view('headline/index', [
            'title' => 'Headline Management',
            'breadcrumb' => 'Headline / Placements',
            'availablePosts' => array_values($availablePosts),
            'headlines' => $headlines,
            'categories' => $categories,
            'filters' => [
                'q' => $keyword ?? '',
                'category' => $categoryFilter ?? 'all'
            ]
        ]);
    }

    public function add()
    {
        $articleId = $this->request->getPost('article_id');
        $posts = DataRepository::get('posts');
        $headlines = DataRepository::get('active_headlines') ?? [];

        // Check if already in headlines
        $exists = false;
        foreach ($headlines as $h) {
            if ($h['id'] == $articleId) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            foreach ($posts as $p) {
                if ($p['id'] == $articleId) {
                    $headlines[] = $p;
                    break;
                }
            }
            DataRepository::save('active_headlines', $headlines);
            return redirect()->to('/headline')->with('success', 'Article added to headline queue.');
        }

        return redirect()->to('/headline')->with('info', 'Article is already in headline queue.');
    }

    public function remove($id)
    {
        $headlines = DataRepository::get('active_headlines') ?? [];
        $headlines = array_values(array_filter($headlines, fn($h) => $h['id'] != $id));
        DataRepository::save('active_headlines', $headlines);

        return redirect()->to('/headline')->with('success', 'Article removed from headline list.');
    }

    public function save()
    {
        $targetCategory = $this->request->getPost('target_category') ?: 'Headline';
        return redirect()->to('/headline')->with('success', "Headline list for '{$targetCategory}' published to user page successfully!");
    }
}
