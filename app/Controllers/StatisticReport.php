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

        // Date range filtering
        $todayStr = date('Y-m-d');
        $defaultFromStr = date('Y-m-d', strtotime('-30 days'));

        $fromDate = $this->request->getGet('from') ?: $defaultFromStr;
        $toDate = $this->request->getGet('to') ?: $todayStr;

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
                ref_entity_principal_id AS id,
                attr_display_name AS name,
                attr_identifier AS username,
                attr_contact_val AS email,
                attr_role_code AS role
            ')->findAll();

        $authorUsers = array_filter($allUsers, fn($u) => ($u['role'] ?? '') === 'Author');
        $editorUsers = array_filter($allUsers, fn($u) => in_array($u['role'] ?? '', ['Editor', 'Admin']));
        $contribUsers = array_filter($allUsers, fn($u) => ($u['role'] ?? '') === 'Contributor');

        // Build matrices
        $authorMatrix = $this->buildMatrix($authorUsers, $dateColumns, $fromDate, $toDate, true);
        $editorMatrix = $this->buildMatrix($editorUsers, $dateColumns, $fromDate, $toDate, false);
        $contribMatrix = $this->buildMatrix($contribUsers, $dateColumns, $fromDate, $toDate, true);

        $totalFilteredArticles = $this->articleModel->db->table('entity_nano')
            ->where('entity_date >=', date('Y-m-d 00:00:00', $fromTimestamp))
            ->where('entity_date <=', date('Y-m-d 23:59:59', $toTimestamp))
            ->countAllResults();

        return view('statistic/report', [
            'title'                 => 'Editorial Performance Matrix Report',
            'breadcrumb'            => 'Insights / Editorial Performance',
            'tab'                   => $tab,
            'fromDate'              => $fromDate,
            'toDate'                => $toDate,
            'dateColumns'           => $dateColumns,
            'authorMatrix'          => $authorMatrix,
            'editorMatrix'          => $editorMatrix,
            'contribMatrix'         => $contribMatrix,
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
                $res = $db->table('entity_nano n')
                    ->select('DATE(n.entity_date) AS pdate, COUNT(DISTINCT n.ref_entity_nano_id) AS cnt, SUM(n.view_count) AS vsum')
                    ->join('entity_nano_aubert_link nal', 'nal.ref_entity_nano_id = n.ref_entity_nano_id', 'left')
                    ->join('entity_aubert a', 'a.ref_entity_aubert_id = nal.ref_entity_aubert_id', 'left')
                    ->where('n.entity_date >=', $fromDate . ' 00:00:00')
                    ->where('n.entity_date <=', $toDate . ' 23:59:59')
                    ->groupStart()
                        ->like('a.aubert_name_a', $u['name'])
                        ->orWhere('n.ref_entity_acc_id', $u['id'])
                    ->groupEnd()
                    ->groupBy('DATE(n.entity_date)')
                    ->get()->getResultArray();
            } else {
                // Editor query matching ref_entity_acc_id
                $res = $db->table('entity_nano n')
                    ->select('DATE(n.entity_date) AS pdate, COUNT(DISTINCT n.ref_entity_nano_id) AS cnt, SUM(n.view_count) AS vsum')
                    ->where('n.ref_entity_acc_id', $u['id'])
                    ->where('n.entity_date >=', $fromDate . ' 00:00:00')
                    ->where('n.entity_date <=', $toDate . ' 23:59:59')
                    ->groupBy('DATE(n.entity_date)')
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
}
