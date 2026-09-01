<?php

namespace App\Controllers;

use App\Models\DataRepository;

class Dashboard extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts');
        $users = DataRepository::get('users');

        $totalViews = array_sum(array_column($posts, 'views'));
        $publishedCount = count(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'Published'));
        $draftCount = count(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'Draft'));
        $scheduledCount = count(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'Scheduled'));
        $reviewCount = count(array_filter($posts, fn($p) => ($p['status'] ?? '') === 'Review'));

        // Sort by views desc for top performing
        $topArticles = $posts;
        usort($topArticles, fn($a, $b) => $b['views'] <=> $a['views']);
        $topArticles = array_slice($topArticles, 0, 5);

        return view('dashboard/index', [
            'title' => 'Newsroom Overview',
            'breadcrumb' => 'Dashboard',
            'posts' => $posts,
            'topArticles' => $topArticles,
            'metrics' => [
                'todayArticles' => count($posts),
                'published' => $publishedCount,
                'drafts' => $draftCount,
                'scheduled' => $scheduledCount,
                'review' => $reviewCount,
                'totalViews' => number_format($totalViews + 1200000),
            ]
        ]);
    }
}
