<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\AdminModel;

class StatisticReport extends BaseController
{
    protected ArticleModel $articleModel;
    protected AdminModel $adminModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->adminModel = new AdminModel();
    }

    public function index()
    {
        $tab = $this->request->getGet('tab') ?: 'author';

        $fromDate = $this->request->getGet('from') ?: date('Y-m-d', strtotime('-30 days'));
        $toDate   = $this->request->getGet('to') ?: date('Y-m-d');

        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate);

        if ($toTimestamp < $fromTimestamp) {
            $temp = $fromTimestamp;
            $fromTimestamp = $toTimestamp;
            $toTimestamp = $temp;
            $fromDate = date('Y-m-d', $fromTimestamp);
            $toDate = date('Y-m-d', $toTimestamp);
        }

        // Custom range: allow up to 90 days for flexible in-depth editorial analysis
        $diffDays = ($toTimestamp - $fromTimestamp) / (60 * 60 * 24);
        if ($diffDays > 90) {
            $fromTimestamp = strtotime($toDate . ' -90 days');
            $fromDate = date('Y-m-d', $fromTimestamp);
        }

        // Date columns list
        $dateColumns = [];
        $curr = $fromTimestamp;
        while ($curr <= $toTimestamp) {
            $dateColumns[] = [
                'full_date'   => date('Y-m-d', $curr),
                'short_label' => date('j/n', $curr),
                'day_name'    => date('D', $curr),
            ];
            $curr = strtotime('+1 day', $curr);
        }

        // Get users list
        $allUsers = $this->adminModel->select('
                userID AS id,
                name,
                username,
                email,
                roleName AS role
            ')->findAll();

        $authorUsers = array_filter($allUsers, fn($u) => in_array(strtolower($u['role'] ?? ''), ['editor', 'redpel', 'riset']));
        $editorUsers = array_filter($allUsers, fn($u) => in_array(strtolower($u['role'] ?? ''), ['administrator', 'redpel', 'editor']));

        // Build matrices (Author & Editor)
        $fullAuthorMatrix = $this->buildMatrix($authorUsers, $dateColumns, $fromDate, $toDate, true);
        $fullEditorMatrix = $this->buildMatrix($editorUsers, $dateColumns, $fromDate, $toDate, false);

        // Pagination for matrix rows
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = 15;

        $targetFullMatrix = ($tab === 'editor') ? $fullEditorMatrix : $fullAuthorMatrix;
        $totalItems = count($targetFullMatrix);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $authorMatrix = array_slice($fullAuthorMatrix, ($tab === 'author') ? $offset : 0, ($tab === 'author') ? $perPage : count($fullAuthorMatrix));
        $editorMatrix = array_slice($fullEditorMatrix, ($tab === 'editor') ? $offset : 0, ($tab === 'editor') ? $perPage : count($fullEditorMatrix));

        $pagination = [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'from'       => $totalItems > 0 ? $offset + 1 : 0,
            'to'         => min($offset + $perPage, $totalItems),
        ];

        $totalFilteredArticles = $this->articleModel->db->table('article')
            ->where('articleDate >=', date('Y-m-d 00:00:00', $fromTimestamp))
            ->where('articleDate <=', date('Y-m-d 23:59:59', $toTimestamp))
            ->countAllResults();

        // Default tab fallback
        if (!in_array($tab, ['author', 'editor', 'heatmap'])) {
            $tab = 'author';
        }

        $heatmapData = $this->getHourlyHeatmapData($fromDate, $toDate);

        return view('statistic/report', [
            'title'                 => 'Editorial Performance Matrix Report',
            'breadcrumb'            => 'Insights / Editorial Performance',
            'tab'                   => $tab,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'dateColumns'           => $dateColumns,
            'authorMatrix'          => $authorMatrix,
            'editorMatrix'          => $editorMatrix,
            'heatmapData'           => $heatmapData,
            'fullPersonnelCount'    => $totalItems,
            'pagination'            => $pagination,
            'totalFilteredArticles' => $totalFilteredArticles,
        ]);
    }

    protected function buildMatrix(array $users, array $dateColumns, string $fromDate, string $toDate, bool $isAuthor): array
    {
        $matrix = [];
        $db = $this->articleModel->db;

        foreach ($users as $u) {
            $dailyCounts = [];
            foreach ($dateColumns as $col) {
                $dailyCounts[$col['full_date']] = 0;
            }

            if ($isAuthor) {
                // Author query matching author link or byline
                $res = $db->table('article n')
                    ->select('DATE(n.articleDate) AS pdate, COUNT(DISTINCT n.articleID) AS cnt, SUM(n.viewed) AS vsum')
                    ->join('articleauthor nal', 'nal.articleID = n.articleID', 'left')
                    ->join('authors a', 'a.authorID = nal.authorID', 'left')
                    ->where('n.articleDate >=', $fromDate . ' 00:00:00')
                    ->where('n.articleDate <=', $toDate . ' 23:59:59')
                    ->groupStart()
                        ->like('a.authorName', $u['name'])
                        ->orWhere('n.userID', $u['id'])
                    ->groupEnd()
                    ->groupBy('DATE(n.articleDate)')
                    ->get()->getResultArray();
            } else {
                // Editor query matching userID
                $res = $db->table('article n')
                    ->select('DATE(n.articleDate) AS pdate, COUNT(DISTINCT n.articleID) AS cnt, SUM(n.viewed) AS vsum')
                    ->where('n.userID', $u['id'])
                    ->where('n.articleDate >=', $fromDate . ' 00:00:00')
                    ->where('n.articleDate <=', $toDate . ' 23:59:59')
                    ->groupBy('DATE(n.articleDate)')
                    ->get()->getResultArray();
            }

            $publishedCount = 0;
            $totalViews = 0;
            foreach ($res as $r) {
                if (isset($dailyCounts[$r['pdate']])) {
                    $dailyCounts[$r['pdate']] = (int)$r['cnt'];
                }
                $publishedCount += (int)$r['cnt'];
                $totalViews += (int)($r['vsum'] ?? 0);
            }

            $matrix[] = [
                'user'            => $u,
                'name'            => $u['name'],
                'role'            => $u['role'],
                'daily_counts'    => $dailyCounts,
                'published_count' => $publishedCount,
                'total_views'     => $totalViews,
            ];
        }

        usort($matrix, fn($a, $b) => $b['published_count'] <=> $a['published_count']);
        return $matrix;
    }

    /**
     * Replika legacy: Article::DailyArticleProductivity($from, $to)
     * Hourly productivity heatmap (24 jam x rentang tanggal)
     * Tabel: article (R)
     */
    public function hourlyHeatmap()
    {
        $fromDate = $this->request->getGet('from') ?: date('Y-m-d', strtotime('-7 days'));
        $toDate = $this->request->getGet('to') ?: date('Y-m-d');

        $matrix = $this->getHourlyHeatmapData($fromDate, $toDate);

        return $this->response->setJSON([
            'status' => 'success',
            'from'   => $fromDate,
            'to'     => $toDate,
            'data'   => $matrix,
        ]);
    }

    /**
     * Get hourly productivity matrix array
     */
    protected function getHourlyHeatmapData(string $fromDate, string $toDate): array
    {
        $db = $this->articleModel->db;
        $records = $db->table('article')
            ->select('DATE(articleDate) AS pdate, HOUR(articleDate) AS phour, COUNT(*) AS total')
            ->where('articleDate >=', $fromDate . ' 00:00:00')
            ->where('articleDate <=', $toDate . ' 23:59:59')
            ->whereIn('LOWER(status)', ['publish', 'published'])
            ->groupBy('DATE(articleDate), HOUR(articleDate)')
            ->orderBy('pdate', 'DESC')
            ->orderBy('phour', 'ASC')
            ->get()->getResultArray();

        $matrix = [];
        foreach ($records as $r) {
            $date = $r['pdate'];
            $hour = (int)$r['phour'];
            if (!isset($matrix[$date])) {
                $matrix[$date] = array_fill(0, 24, 0);
            }
            $matrix[$date][$hour] = (int)$r['total'];
        }

        return $matrix;
    }
}
