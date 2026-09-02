<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Kanal extends BaseController
{
    public function master()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $posts = DataRepository::get('posts') ?? [];

        // Compute real article counts per kanal
        foreach ($kanals as &$k) {
            $kPosts = array_filter($posts, fn($p) => ($p['kanal'] ?? ($p['category'] ?? '')) === $k['name'] || str_starts_with($p['kanal'] ?? '', $k['name']));
            $k['article_count'] = count($kPosts);
            $k['total_views'] = array_sum(array_column($kPosts, 'views'));
            $k['sub_count'] = count($k['sub_kanals'] ?? []);
        }

        return view('kanal/master', [
            'title' => 'Master Kanal Management',
            'breadcrumb' => 'Kanal / Master Kanal',
            'kanals' => $kanals
        ]);
    }

    public function subKanal()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $posts = DataRepository::get('posts') ?? [];

        // Flatten all sub-kanals with parent reference
        $subList = [];
        foreach ($kanals as $k) {
            foreach ($k['sub_kanals'] ?? [] as $sub) {
                $subPosts = array_filter($posts, fn($p) => ($p['sub_kanal'] ?? '') === $sub['name'] || stripos($p['category'] ?? '', $sub['name']) !== false);
                $subList[] = [
                    'id' => $sub['id'],
                    'name' => $sub['name'],
                    'slug' => $sub['slug'],
                    'parent_name' => $k['name'],
                    'parent_slug' => $k['slug'],
                    'article_count' => count($subPosts),
                    'total_views' => array_sum(array_column($subPosts, 'views')),
                ];
            }
        }

        return view('kanal/sub_kanal', [
            'title' => 'Sub Kanal Management',
            'breadcrumb' => 'Kanal / Sub Kanal',
            'kanals' => $kanals,
            'subKanals' => $subList
        ]);
    }

    public function stats()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $posts = DataRepository::get('posts') ?? [];

        $kanalPerformance = [];
        foreach ($kanals as $k) {
            $kPosts = array_filter($posts, fn($p) => ($p['kanal'] ?? ($p['category'] ?? '')) === $k['name'] || str_starts_with($p['kanal'] ?? '', $k['name']));
            $published = array_filter($kPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $views = array_sum(array_column($kPosts, 'views'));
            $premium = count(array_filter($kPosts, fn($p) => !empty($p['is_premium'])));

            $kanalPerformance[] = [
                'name' => $k['name'],
                'slug' => $k['slug'],
                'sub_count' => count($k['sub_kanals'] ?? []),
                'total_articles' => count($kPosts),
                'published_count' => count($published),
                'total_views' => $views,
                'avg_views' => count($published) > 0 ? round($views / count($published)) : 0,
                'premium_count' => $premium,
            ];
        }
        usort($kanalPerformance, fn($a, $b) => $b['total_views'] <=> $a['total_views']);

        return view('kanal/stats', [
            'title' => 'Performa Kanal & Distribusi Audiens',
            'breadcrumb' => 'Kanal / Performa Kanal',
            'kanalPerformance' => $kanalPerformance,
            'totalArticles' => count($posts),
            'totalViews' => array_sum(array_column($posts, 'views')),
        ]);
    }

    public function saveMaster()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $name = $this->request->getPost('name');

        $newId = count($kanals) > 0 ? max(array_column($kanals, 'id')) + 1 : 1;
        $newItem = [
            'id' => $newId,
            'name' => $name,
            'slug' => url_title($name, '-', true),
            'sub_kanals' => []
        ];

        $kanals[] = $newItem;
        DataRepository::save('kanals', $kanals);

        return redirect()->to('/kanal/master')->with('success', 'Master Kanal berhasil ditambahkan!');
    }

    public function saveSub()
    {
        $kanals = DataRepository::get('kanals') ?? [];
        $parentId = (int)$this->request->getPost('parent_id');
        $name = $this->request->getPost('name');

        foreach ($kanals as &$k) {
            if ($k['id'] === $parentId) {
                if (!isset($k['sub_kanals'])) $k['sub_kanals'] = [];
                $newSubId = count($k['sub_kanals']) > 0 ? max(array_column($k['sub_kanals'], 'id')) + 1 : ($parentId * 100 + 1);
                $k['sub_kanals'][] = [
                    'id' => $newSubId,
                    'name' => $name,
                    'slug' => url_title($name, '-', true),
                ];
                break;
            }
        }

        DataRepository::save('kanals', $kanals);
        return redirect()->to('/kanal/sub')->with('success', 'Sub Kanal berhasil ditambahkan!');
    }
}
