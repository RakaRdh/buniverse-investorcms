<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\AdminModel;
use App\Models\AuthorModel;

class StatisticReport extends BaseController
{
    protected ArticleModel $articleModel;
    protected AdminModel $adminModel;
    protected AuthorModel $authorModel;

    public function __construct()
    {
        $this->articleModel = new ArticleModel();
        $this->adminModel = new AdminModel();
        $this->authorModel = new AuthorModel();
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

        // 1. Get Distinct Authors from `authors` table (Finding D8 fix)
        $rawAuthors = $this->authorModel->select('authorID AS id, authorName AS name, email')->findAll();
        $authorList = array_map(function($a) {
            return [
                'id'       => $a['id'],
                'name'     => trim(rtrim($a['name'], '*)')),
                'raw_name' => $a['name'],
                'email'    => $a['email'] ?? '',
                'role'     => 'Author'
            ];
        }, $rawAuthors);

        // 2. Get Editors from `useradministrator` table
        $editorUsers = $this->adminModel->select('
                userID AS id,
                name,
                username,
                email,
                roleName AS role
            ')->findAll();

        // Build matrices (Author & Editor segregations)
        $fullAuthorMatrix = $this->buildMatrix($authorList, $dateColumns, $fromDate, $toDate, true);
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
        if (!in_array($tab, ['author', 'editor', 'heatmap', 'publish', 'popular'])) {
            $tab = 'author';
        }

        $heatmapData = [];
        $publishLog = [];
        $popularArticles = [];

        if ($tab === 'heatmap') {
            $heatmapData = $this->getHourlyHeatmapData($fromDate, $toDate);
        } elseif ($tab === 'publish') {
            $publishLog = $this->getPublishLogData($fromDate, $toDate, $page, $perPage);
            $totalItems = $publishLog['total'];
            $totalPages = max(1, (int)ceil($totalItems / $perPage));
            $pagination['totalItems'] = $totalItems;
            $pagination['totalPages'] = $totalPages;
            $pagination['from'] = $totalItems > 0 ? ($page - 1) * $perPage + 1 : 0;
            $pagination['to'] = min($page * $perPage, $totalItems);
        } elseif ($tab === 'popular') {
            $popularArticles = $this->getPopularArticlesData($fromDate, $toDate, 50);
            $totalItems = count($popularArticles);
            $totalPages = 1;
            $pagination['totalItems'] = $totalItems;
            $pagination['totalPages'] = 1;
            $pagination['from'] = $totalItems > 0 ? 1 : 0;
            $pagination['to'] = $totalItems;
        }

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
            'publishLog'            => $publishLog['list'] ?? [],
            'popularArticles'       => $popularArticles,
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
                // Author query matching author link by authorID or authorName
                $res = $db->table('article n')
                    ->select('DATE(n.articleDate) AS pdate, COUNT(DISTINCT n.articleID) AS cnt, SUM(n.viewed) AS vsum')
                    ->join('articleauthor nal', 'nal.articleID = n.articleID', 'inner')
                    ->where('nal.authorID', $u['id'])
                    ->where('n.articleDate >=', $fromDate . ' 00:00:00')
                    ->where('n.articleDate <=', $toDate . ' 23:59:59')
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
     * Standalone Publish Log
     * Route: /statistic/publish-log
     * Matriks Publikasi: Baris = Bulan (Januari - Desember), Kolom = Tanggal (1 - 31)
     * Menampilkan jumlah artikel terbit per tanggal sepanjang tahun.
     */
    public function publishLog()
    {
        $currentYear = (int)date('Y');
        $year = (int)($this->request->getGet('year') ?: $currentYear);
        if ($year < 2000 || $year > ($currentYear + 5)) {
            $year = $currentYear;
        }

        $matrix = $this->getYearlyPublishMatrix($year);

        return view('statistic/publish_log', [
            'title'      => 'Publish Log',
            'breadcrumb' => 'Insights / Publish Log',
            'year'       => $year,
            'matrix'     => $matrix,
        ]);
    }

    /**
     * Build matrix for yearly publish log: Month (1-12) x Day (1-31)
     */
    protected function getYearlyPublishMatrix(int $year): array
    {
        $db = $this->articleModel->db;

        $startDate = sprintf('%04d-01-01 00:00:00', $year);
        $endDate   = sprintf('%04d-12-31 23:59:59', $year);

        // Fetch articles grouped by month, day, and collect distinct editor initials
        $records = $db->table('article a')
            ->select('
                MONTH(a.articleDate) AS mo,
                DAY(a.articleDate) AS dy,
                COUNT(a.articleID) AS total_articles,
                GROUP_CONCAT(DISTINCT NULLIF(TRIM(u.alias), "") ORDER BY u.alias SEPARATOR ", ") AS initials
            ')
            ->join('useradministrator u', 'u.userID = a.userID', 'left')
            ->where('a.articleDate >=', $startDate)
            ->where('a.articleDate <=', $endDate)
            ->whereIn('LOWER(a.status)', ['publish', 'published'])
            ->groupBy('MONTH(a.articleDate), DAY(a.articleDate)')
            ->get()->getResultArray();

        $lookup = [];
        foreach ($records as $r) {
            $mo = (int)$r['mo'];
            $dy = (int)$r['dy'];
            $lookup[$mo][$dy] = [
                'count'    => (int)$r['total_articles'],
                'initials' => $r['initials'] ?: '',
            ];
        }

        $monthNames = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $matrix = [];
        for ($m = 1; $m <= 12; $m++) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $year);
            $days = [];
            $monthTotal = 0;

            for ($d = 1; $d <= 31; $d++) {
                if ($d <= $daysInMonth) {
                    $item = $lookup[$m][$d] ?? ['count' => 0, 'initials' => ''];
                    $days[$d] = [
                        'valid'    => true,
                        'count'    => $item['count'],
                        'initials' => $item['initials'],
                        'date'     => sprintf('%04d-%02d-%02d', $year, $m, $d)
                    ];
                    $monthTotal += $item['count'];
                } else {
                    $days[$d] = [
                        'valid'    => false,
                        'count'    => 0,
                        'initials' => '',
                        'date'     => null
                    ];
                }
            }

            $matrix[$m] = [
                'month_num'   => $m,
                'month_name'  => $monthNames[$m],
                'days'        => $days,
                'month_total' => $monthTotal,
            ];
        }

        return $matrix;
    }

    /**
     * Standalone Popular Articles View
     * Route: /statistic/popular
     */
    public function popular()
    {
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

        $popularArticles = $this->getPopularArticlesData($fromDate, $toDate, 100);

        return view('statistic/popular', [
            'title'           => 'Artikel Terpopuler',
            'breadcrumb'      => 'Insights / Artikel Populer',
            'fromDate'        => $fromDate,
            'toDate'          => $toDate,
            'popularArticles' => $popularArticles,
        ]);
    }

    /**
     * Standalone Heatmap 24 Jam (Supports both Month mode & Custom Range mode)
     * Route: /statistic/heatmap
     */
    public function heatmap()
    {
        $mode = $this->request->getGet('mode') ?: 'month';
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');

        $year = (int)($this->request->getGet('year') ?: $currentYear);
        $month = (int)($this->request->getGet('month') ?: $currentMonth);

        // Sanitize month and year
        if ($month < 1 || $month > 12) {
            $month = $currentMonth;
        }
        if ($year < 2000 || $year > ($currentYear + 5)) {
            $year = $currentYear;
        }

        if ($mode === 'month') {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate   = date('Y-m-t', strtotime($startDate));
            $fromDate  = $startDate;
            $toDate    = $endDate;
        } else {
            $mode     = 'range';
            $fromDate = $this->request->getGet('from') ?: date('Y-m-d', strtotime('-14 days'));
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

            // Cap custom range up to 90 days to prevent browser rendering bottlenecks
            $diffDays = ($toTimestamp - $fromTimestamp) / (60 * 60 * 24);
            if ($diffDays > 90) {
                $fromTimestamp = strtotime($toDate . ' -90 days');
                $fromDate = date('Y-m-d', $fromTimestamp);
            }
        }

        $heatmapData = $this->getHourlyHeatmapData($fromDate, $toDate);

        return view('statistic/heatmap', [
            'title'       => 'Heatmap Distribusi Jam Terbit (24 Jam)',
            'breadcrumb'  => 'Insights / Heatmap 24 Jam',
            'mode'        => $mode,
            'year'        => $year,
            'month'       => $month,
            'fromDate'    => $fromDate,
            'toDate'      => $toDate,
            'heatmapData' => $heatmapData,
        ]);
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

    /**
     * Legacy parity: artikel-publish log with editor initial (u.alias AS kode)
     */
    protected function getPublishLogData(string $fromDate, string $toDate, int $page = 1, int $perPage = 20): array
    {
        $db = $this->articleModel->db;

        $builder = $db->table('article a')
            ->select('
                a.articleID,
                a.headline AS articleTitle,
                a.articleDate,
                a.createdAt AS createDate,
                a.status,
                a.viewed,
                u.alias AS kode,
                u.name AS editor_name,
                c.categoryName AS kanal_title
            ')
            ->join('useradministrator u', 'u.userID = a.userID', 'left')
            ->join('articlecategory ac', 'ac.articleID = a.articleID', 'left')
            ->join('category c', 'c.categoryID = ac.categoryID', 'left')
            ->where('a.articleDate >=', $fromDate . ' 00:00:00')
            ->where('a.articleDate <=', $toDate . ' 23:59:59')
            ->whereIn('LOWER(a.status)', ['publish', 'published']);

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $offset = ($page - 1) * $perPage;
        $list = $builder->orderBy('a.articleDate', 'DESC')
            ->limit($perPage, $offset)
            ->get()->getResultArray();

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    /**
     * Legacy parity: popular articles ranking
     */
    protected function getPopularArticlesData(string $fromDate, string $toDate, int $limit = 50): array
    {
        $db = $this->articleModel->db;

        return $db->table('article a')
            ->select('
                a.articleID,
                a.headline AS articleTitle,
                a.articleDate,
                a.viewed,
                a.status,
                c.categoryName AS kanal_title,
                u.name AS editor_name
            ')
            ->join('useradministrator u', 'u.userID = a.userID', 'left')
            ->join('articlecategory ac', 'ac.articleID = a.articleID', 'left')
            ->join('category c', 'c.categoryID = ac.categoryID', 'left')
            ->where('a.articleDate >=', $fromDate . ' 00:00:00')
            ->where('a.articleDate <=', $toDate . ' 23:59:59')
            ->whereIn('LOWER(a.status)', ['publish', 'published'])
            ->orderBy('a.viewed', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Export Endpoint for Spreadsheet / CSV download
     */
    public function export()
    {
        $tab = $this->request->getGet('tab') ?: 'publish';
        $fromDate = $this->request->getGet('from') ?: date('Y-m-d', strtotime('-30 days'));
        $toDate = $this->request->getGet('to') ?: date('Y-m-d');

        $filename = "editorial_report_{$tab}_{$fromDate}_{$toDate}.csv";

        $output = fopen('php://temp', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        if ($tab === 'publish') {
            fputcsv($output, ['ID Artikel', 'Judul Berita', 'Kanal', 'Tanggal Terbit', 'Tanggal Dibuat', 'Inisial Editor', 'Nama Editor', 'Total Views']);
            $db = $this->articleModel->db;
            $items = $db->table('article a')
                ->select('
                    a.articleID,
                    a.headline AS articleTitle,
                    c.categoryName AS kanal_title,
                    a.articleDate,
                    a.createdAt AS createDate,
                    u.alias AS kode,
                    u.name AS editor_name,
                    a.viewed
                ')
                ->join('useradministrator u', 'u.userID = a.userID', 'left')
                ->join('articlecategory ac', 'ac.articleID = a.articleID', 'left')
                ->join('category c', 'c.categoryID = ac.categoryID', 'left')
                ->where('a.articleDate >=', $fromDate . ' 00:00:00')
                ->where('a.articleDate <=', $toDate . ' 23:59:59')
                ->whereIn('LOWER(a.status)', ['publish', 'published'])
                ->orderBy('a.articleDate', 'DESC')
                ->get()->getResultArray();

            foreach ($items as $item) {
                fputcsv($output, [
                    $item['articleID'],
                    $item['articleTitle'],
                    $item['kanal_title'] ?? '-',
                    $item['articleDate'],
                    $item['createDate'],
                    $item['kode'] ?? '-',
                    $item['editor_name'] ?? '-',
                    $item['viewed'] ?? 0,
                ]);
            }
        } elseif ($tab === 'popular') {
            fputcsv($output, ['Peringkat', 'ID Artikel', 'Judul Berita', 'Kanal', 'Tanggal Terbit', 'Nama Editor', 'Total Pembaca (Views)']);
            $items = $this->getPopularArticlesData($fromDate, $toDate, 100);
            $rank = 1;
            foreach ($items as $item) {
                fputcsv($output, [
                    $rank++,
                    $item['articleID'],
                    $item['articleTitle'],
                    $item['kanal_title'] ?? '-',
                    $item['articleDate'],
                    $item['editor_name'] ?? '-',
                    $item['viewed'] ?? 0,
                ]);
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csvContent);
    }
}

