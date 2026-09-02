<?php

namespace App\Controllers;

use App\Models\DataRepository;

class StatisticReport extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts') ?? [];
        $users = DataRepository::get('users') ?? [];

        // Date range filtering (Default: 30 days ago until today)
        $todayStr = date('Y-m-d');
        $defaultFromStr = date('Y-m-d', strtotime('-30 days'));

        $fromDate = $this->request->getGet('from') ?: $defaultFromStr;
        $toDate = $this->request->getGet('to') ?: $todayStr;

        // Enforce max 30 days logic safely
        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate);

        if ($toTimestamp < $fromTimestamp) {
            $temp = $fromTimestamp;
            $fromTimestamp = $toTimestamp;
            $toTimestamp = $temp;
            $fromDate = date('Y-m-d', $fromTimestamp);
            $toDate = date('Y-m-d', $toTimestamp);
        }

        $diffDays = ($toTimestamp - $fromTimestamp) / (60 * 60 * 24);
        if ($diffDays > 30) {
            $fromTimestamp = strtotime($toDate . ' -30 days');
            $fromDate = date('Y-m-d', $fromTimestamp);
        }

        // Filter posts within range
        $filteredPosts = array_filter($posts, function($p) use ($fromTimestamp, $toTimestamp) {
            $pubDateStr = $p['publish_date'] ?? $p['create_date'] ?? '';
            $ts = strtotime($pubDateStr);
            if (!$ts) return false;
            // compare date bounds (inclusive)
            $pDate = strtotime(date('Y-m-d', $ts));
            return ($pDate >= strtotime(date('Y-m-d', $fromTimestamp)) && $pDate <= strtotime(date('Y-m-d', $toTimestamp)));
        });

        // 1. Author Report (users with role 'Author')
        $authorUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'Author');
        $authorReport = [];
        foreach ($authorUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username'] || stripos($p['author'] ?? '', $u['name']) !== false);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $views = array_sum(array_column($uPosts, 'views'));
            
            $authorReport[] = [
                'user' => $u,
                'total_articles' => count($uPosts),
                'published_articles' => count($published),
                'total_views' => $views,
                'avg_views' => count($published) > 0 ? round($views / count($published)) : 0,
            ];
        }
        usort($authorReport, fn($a, $b) => $b['published_articles'] <=> $a['published_articles']);

        // 2. Editor Report (users with role 'Editor' or 'Admin')
        $editorUsers = array_filter($users, fn($u) => in_array($u['role'] ?? '', ['Editor', 'Admin']));
        $editorReport = [];
        foreach ($editorUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username']);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $views = array_sum(array_column($uPosts, 'views'));

            $editorReport[] = [
                'user' => $u,
                'total_reviewed' => count($uPosts),
                'published_articles' => count($published),
                'total_views' => $views,
            ];
        }
        usort($editorReport, fn($a, $b) => $b['published_articles'] <=> $a['published_articles']);

        // 3. Contributor Report (users with role 'Contributor')
        $contribUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'Contributor');
        $contribReport = [];
        foreach ($contribUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username'] || stripos($p['author'] ?? '', $u['name']) !== false);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $draft = array_filter($uPosts, fn($p) => in_array($p['status'] ?? '', ['Draft', 'Review']));

            $contribReport[] = [
                'user' => $u,
                'total_articles' => count($uPosts),
                'published_count' => count($published),
                'pending_count' => count($draft),
            ];
        }
        usort($contribReport, fn($a, $b) => $b['total_articles'] <=> $a['total_articles']);

        // 4. Daily Article Releases (articles per day in range)
        $dailyReleases = [];
        $curr = $fromTimestamp;
        while ($curr <= $toTimestamp) {
            $dStr = date('Y-m-d', $curr);
            $dFormatted = date('d M Y', $curr);
            $dayArticles = array_filter($filteredPosts, function($p) use ($dStr) {
                $pubDateStr = $p['publish_date'] ?? $p['create_date'] ?? '';
                $ts = strtotime($pubDateStr);
                return $ts && date('Y-m-d', $ts) === $dStr && ($p['status'] ?? '') === 'Published';
            });
            $totalViews = array_sum(array_column($dayArticles, 'views'));

            $dailyReleases[] = [
                'date_raw' => $dStr,
                'date_formatted' => $dFormatted,
                'day_name' => date('l', $curr),
                'count' => count($dayArticles),
                'views' => $totalViews,
            ];
            $curr = strtotime('+1 day', $curr);
        }
        // reverse to show newest date first
        $dailyReleases = array_reverse($dailyReleases);

        return view('statistic/report', [
            'title' => 'Editorial Productivity & Release Report',
            'breadcrumb' => 'Insights / Editorial Report',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalFilteredArticles' => count($filteredPosts),
            'authorReport' => $authorReport,
            'editorReport' => $editorReport,
            'contribReport' => $contribReport,
            'dailyReleases' => $dailyReleases,
        ]);
    }
}
