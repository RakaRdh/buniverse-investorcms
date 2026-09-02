<?php

namespace App\Controllers;

use App\Models\DataRepository;

class StatisticReport extends BaseController
{
    public function index()
    {
        $posts = DataRepository::get('posts') ?? [];
        $users = DataRepository::get('users') ?? [];

        // Tab selection: 'author' (default) or 'editor' or 'contributor' or 'daily'
        $tab = $this->request->getGet('tab') ?: 'author';

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

        // Build list of dates in the range for column headers (format: '3/9', '4/9', etc.)
        $dateColumns = [];
        $curr = $fromTimestamp;
        while ($curr <= $toTimestamp) {
            $dateColumns[] = [
                'full_date' => date('Y-m-d', $curr),
                'short_label' => date('j/n', $curr), // e.g. 3/9
                'day_name' => date('D', $curr),
            ];
            $curr = strtotime('+1 day', $curr);
        }

        // Filter posts within range
        $filteredPosts = array_filter($posts, function($p) use ($fromTimestamp, $toTimestamp) {
            $pubDateStr = $p['publish_date'] ?? $p['create_date'] ?? '';
            $ts = strtotime($pubDateStr);
            if (!$ts) return false;
            $pDate = strtotime(date('Y-m-d', $ts));
            return ($pDate >= strtotime(date('Y-m-d', $fromTimestamp)) && $pDate <= strtotime(date('Y-m-d', $toTimestamp)));
        });

        // 1. Author Matrix Report (Users with role 'Author')
        $authorUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'Author');
        $authorMatrix = [];
        foreach ($authorUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username'] || stripos($p['author'] ?? '', $u['name']) !== false);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $totalViews = array_sum(array_column($uPosts, 'views'));

            // Count per date column
            $dailyCounts = [];
            foreach ($dateColumns as $col) {
                $colDate = $col['full_date'];
                $countOnDate = 0;
                foreach ($published as $p) {
                    $pTs = strtotime($p['publish_date'] ?? $p['create_date'] ?? '');
                    if ($pTs && date('Y-m-d', $pTs) === $colDate) {
                        $countOnDate++;
                    }
                }
                $dailyCounts[$colDate] = $countOnDate;
            }

            $authorMatrix[] = [
                'user' => $u,
                'name' => $u['name'],
                'role' => $u['role'],
                'daily_counts' => $dailyCounts,
                'published_count' => count($published),
                'total_views' => $totalViews,
            ];
        }
        usort($authorMatrix, fn($a, $b) => $b['published_count'] <=> $a['published_count']);

        // 2. Editor Matrix Report (Users with role 'Editor' or 'Admin')
        $editorUsers = array_filter($users, fn($u) => in_array($u['role'] ?? '', ['Editor', 'Admin']));
        $editorMatrix = [];
        foreach ($editorUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username']);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $totalViews = array_sum(array_column($uPosts, 'views'));

            $dailyCounts = [];
            foreach ($dateColumns as $col) {
                $colDate = $col['full_date'];
                $countOnDate = 0;
                foreach ($published as $p) {
                    $pTs = strtotime($p['publish_date'] ?? $p['create_date'] ?? '');
                    if ($pTs && date('Y-m-d', $pTs) === $colDate) {
                        $countOnDate++;
                    }
                }
                $dailyCounts[$colDate] = $countOnDate;
            }

            $editorMatrix[] = [
                'user' => $u,
                'name' => $u['name'],
                'role' => $u['role'],
                'daily_counts' => $dailyCounts,
                'published_count' => count($published),
                'total_views' => $totalViews,
            ];
        }
        usort($editorMatrix, fn($a, $b) => $b['published_count'] <=> $a['published_count']);

        // 3. Contributor Matrix Report
        $contribUsers = array_filter($users, fn($u) => ($u['role'] ?? '') === 'Contributor');
        $contribMatrix = [];
        foreach ($contribUsers as $u) {
            $uPosts = array_filter($filteredPosts, fn($p) => ($p['post_by'] ?? '') === $u['username'] || stripos($p['author'] ?? '', $u['name']) !== false);
            $published = array_filter($uPosts, fn($p) => ($p['status'] ?? '') === 'Published');
            $totalViews = array_sum(array_column($uPosts, 'views'));

            $dailyCounts = [];
            foreach ($dateColumns as $col) {
                $colDate = $col['full_date'];
                $countOnDate = 0;
                foreach ($published as $p) {
                    $pTs = strtotime($p['publish_date'] ?? $p['create_date'] ?? '');
                    if ($pTs && date('Y-m-d', $pTs) === $colDate) {
                        $countOnDate++;
                    }
                }
                $dailyCounts[$colDate] = $countOnDate;
            }

            $contribMatrix[] = [
                'user' => $u,
                'name' => $u['name'],
                'role' => $u['role'],
                'daily_counts' => $dailyCounts,
                'published_count' => count($published),
                'total_views' => $totalViews,
            ];
        }

        return view('statistic/report', [
            'title' => 'Editorial Performance Matrix Report',
            'breadcrumb' => 'Insights / Editorial Performance',
            'tab' => $tab,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'dateColumns' => $dateColumns,
            'authorMatrix' => $authorMatrix,
            'editorMatrix' => $editorMatrix,
            'contribMatrix' => $contribMatrix,
            'totalFilteredArticles' => count($filteredPosts),
        ]);
    }
}
