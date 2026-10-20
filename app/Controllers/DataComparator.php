<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DataComparator Controller
 * 
 * Digunakan untuk melakukan komparasi data quality antara DB Legacy ($dbA) dan DB Baru ($dbB)
 * Menggunakan teknik sampling: Top N, Median N, Bottom N, dan Distinct Key.
 */
class DataComparator extends Controller
{
    /**
     * Mapping tabel Legacy (DB A) ke DB Baru (DB B)
     */
    protected array $tableMap = [
        'tblapps'               => 'apps',
        'tblarticle'            => 'article',
        'tblarticleauthor'      => 'articleauthor',
        'tblarticlecategory'    => 'articlecategory',
        'tblarticleheadline'    => 'articleheadline',
        'tblarticlehistory'     => 'articlehistory',
        'tblarticlekeyword'     => 'articlekeyword',
        'tblarticlemeta'        => 'articlemeta',
        'tblarticlestatus'      => 'articlestatus',
        'tblarticletag'         => 'articletag',
        'tblauthors'            => 'authors',
        'tblauthors_current'    => 'authors_current',
        'tblcategory'           => 'category',
        'tblcategory_backup'    => 'category_backup',
        'tblcategorymenu'       => 'categorymenu',
        'tblchartdata'          => 'chartdata',
        'tblcommentar'          => 'commentar',
        'tbleditorlog'          => 'editorlog',
        'tblfeaturecontent'     => 'featurecontent',
        'tblfiles'              => 'files',
        'tblgoogledocs'         => 'googledocs',
        'tblkeyword'            => 'keyword',
        'tblmedia'              => 'media',
        'tblmediaarticle'       => 'mediaarticle',
        'tblmember'             => 'member',
        'tblmemberbackup'       => 'memberbackup',
        'tblmemberfavarticle'   => 'memberfavarticle',
        'tblmemberpost'         => 'memberpost',
        'tblpushnotif'          => 'pushnotif',
        'tblpushregistered'     => 'pushregistered',
        'tblpushtodevices'      => 'pushtodevices',
        'tblpushtodevicescron'  => 'pushtodevicescron',
        'tblsaham'              => 'saham',
        'tblsaham_emiten'       => 'saham_emiten',
        'tblsaham_index'        => 'saham_index',
        'tblsaham_sectoral'     => 'saham_sectoral',
        'tblsaham_summary'      => 'saham_summary',
        'tblsaham_topactive'    => 'saham_topactive',
        'tblsaham_topgainers'   => 'saham_topgainers',
        'tblsaham_topvalue'     => 'saham_topvalue',
        'tblsaham_topvolume'    => 'saham_topvolume',
        'tblsphinxindexing'     => 'sphinxindexing',
        'tbltag'                => 'tag',
        'tbltrending'           => 'trending',
        'tbltrendingexclude'    => 'trendingexclude',
        'tbluseradministrator'  => 'useradministrator',
        'tblarticlepoints'      => 'articlepoints',
        'tblarticleheadlinehistory' => 'articleheadlinehistory',
        'tblseo_scores'         => 'seo_scores',
        'tblseoscoring'         => 'seoscoring',
        'tblai_generated_logs'  => 'ai_generated_logs',
        'tblaigenerate'         => 'aigenerate',
    ];

    /**
     * Koneksi database DB A (Legacy) dan DB B (New DB)
     */
    protected function getConnections(): array
    {
        // DB B (New DB - koneksi default CI4)
        $dbB = \Config\Database::connect('default');

        // DB A (Legacy DB 'investor')
        $dbAConfig = [
            'DSN'      => '',
            'hostname' => '127.0.0.1',
            'username' => env('database.default.username', 'root'),
            'password' => env('database.default.password', ''),
            'database' => 'investor',
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug'  => true,
            'charset'  => 'utf8mb4',
            'port'     => (int) (env('database.default.port') ?: 3306),
        ];
        $dbA = \Config\Database::connect($dbAConfig);

        return [$dbA, $dbB];
    }

    /**
     * Endpoint utama untuk mengambil sampel data dan perbandingan
     * URL: /datacomparator/sample?n=3&table=article
     */
    public function sample(): ResponseInterface
    {
        if (ENVIRONMENT === 'production') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $request = service('request');
        $n = 3;
        $specificTable = null;

        if (method_exists($request, 'getGet')) {
            $n = (int) ($request->getGet('n') ?: 3);
            $specificTable = $request->getGet('table') ?: null;
        } elseif (isset($_GET['n'])) {
            $n = (int) $_GET['n'];
            $specificTable = $_GET['table'] ?? null;
        }

        $result = $this->runComparison($specificTable, $n);

        return $this->response->setJSON([
            'status'     => 'success',
            'timestamp'  => date('Y-m-d H:i:s'),
            'sample_size'=> $n,
            'total_tables_checked' => count($result),
            'data'       => $result
        ]);
    }

    /**
     * Menjalankan perbandingan tabel secara programmatic atau CLI
     */
    public function runComparison(?string $specificTable = null, int $n = 3): array
    {
        [$dbA, $dbB] = $this->getConnections();

        $result = [];

        foreach ($this->tableMap as $tableA => $tableB) {
            if ($specificTable && $specificTable !== $tableA && $specificTable !== $tableB) {
                continue;
            }

            $result[$tableA] = $this->compareTableSample($dbA, $dbB, $tableA, $tableB, $n);
        }

        return $result;
    }

    /**
     * Pengambilan data sampling (Top, Mid, Bottom, Distinct) dan komparasi
     */
    protected function compareTableSample($dbA, $dbB, string $tableA, string $tableB, int $n = 3): array
    {
        if (is_cli()) {
            \CodeIgniter\CLI\CLI::write("  -> Processing $tableA vs $tableB...", 'yellow');
        }

        // 1. Dapatkan kolom & Primary Key
        $colsA = $this->getTableColumns($dbA, $tableA);
        $colsB = $this->getTableColumns($dbB, $tableB);

        $pkA = $this->getPrimaryKey($dbA, $tableA);
        $pkB = $this->getPrimaryKey($dbB, $tableB);

        $sortColA = $pkA ?: ($colsA[0] ?? null);
        $sortColB = $pkB ?: ($colsB[0] ?? null);

        // 2. Hitung jumlah baris
        $countA = (int) $dbA->table($tableA)->countAllResults();
        $countB = (int) $dbB->table($tableB)->countAllResults();

        // 3. Ambil sampling (Top, Mid, Bottom) untuk DB A
        $samplesA = $this->extractSamples($dbA, $tableA, $sortColA, $countA, $n);

        // 4. Ambil sampling (Top, Mid, Bottom) untuk DB B
        $samplesB = $this->extractSamples($dbB, $tableB, $sortColB, $countB, $n);

        // 5. Distinct Primary Key (Maksimal 1000 untuk performa)
        $distinctA = $sortColA ? array_column($dbA->table($tableA)->select($sortColA)->limit(1000)->get()->getResultArray(), $sortColA) : [];
        $distinctB = $sortColB ? array_column($dbB->table($tableB)->select($sortColB)->limit(1000)->get()->getResultArray(), $sortColB) : [];

        $diffKeysA = array_values(array_diff($distinctA, $distinctB));
        $diffKeysB = array_values(array_diff($distinctB, $distinctA));

        // 6. Analisis Status
        $colsOnlyA = array_values(array_diff($colsA, $colsB));
        $colsOnlyB = array_values(array_diff($colsB, $colsA));

        $isIdentical = ($countA === $countB) 
                    && empty($colsOnlyA) 
                    && empty($colsOnlyB) 
                    && empty($diffKeysA) 
                    && empty($diffKeysB);

        // Validasi Value pada sampel
        $valueMismatches = [];
        if ($isIdentical && !empty($sortColA) && $sortColA === $sortColB) {
            foreach ($samplesB['top'] as $rowB) {
                $pkVal = $rowB[$sortColB] ?? null;
                if ($pkVal === null) continue;
                $rowA = $dbA->table($tableA)->where($sortColA, $pkVal)->get()->getRowArray();
                if ($rowA) {
                    foreach ($rowB as $col => $valB) {
                        if (array_key_exists($col, $rowA) && (string)$rowA[$col] !== (string)$valB) {
                            $valueMismatches[$pkVal][$col] = [
                                'legacy_A' => $rowA[$col],
                                'new_B'    => $valB,
                            ];
                        }
                    }
                }
            }
            if (!empty($valueMismatches)) {
                $isIdentical = false;
            }
        }

        // Khusus tabel EAV seperti articlemeta: Analisis distinct metakey
        $metaKeyAnalysis = [];
        if (in_array('metakey', $colsA, true) && in_array('metakey', $colsB, true)) {
            $qMetaA = $dbA->table($tableA)->select('metakey, COUNT(*) as cnt')->groupBy('metakey')->orderBy('cnt', 'DESC')->get()->getResultArray();
            $qMetaB = $dbB->table($tableB)->select('metakey, COUNT(*) as cnt')->groupBy('metakey')->orderBy('cnt', 'DESC')->get()->getResultArray();

            $keysA = array_column($qMetaA, 'cnt', 'metakey');
            $keysB = array_column($qMetaB, 'cnt', 'metakey');

            $metaKeyAnalysis = [
                'keys_in_legacy'      => $keysA,
                'keys_in_new'         => $keysB,
                'keys_only_in_legacy' => array_keys(array_diff_key($keysA, $keysB)),
                'keys_only_in_new'    => array_keys(array_diff_key($keysB, $keysA)),
            ];
            if (!empty($metaKeyAnalysis['keys_only_in_legacy']) || !empty($metaKeyAnalysis['keys_only_in_new'])) {
                $isIdentical = false;
            }
        }

        return [
            'legacy_table'    => $tableA,
            'new_table'       => $tableB,
            'primary_key_A'   => $pkA,
            'primary_key_B'   => $pkB,
            'row_count_A'     => $countA,
            'row_count_B'     => $countB,
            'status'          => $isIdentical ? 'IDENTIK' : 'MISMATCH',
            'schema_diff'     => [
                'columns_only_in_legacy' => $colsOnlyA,
                'columns_only_in_new'    => $colsOnlyB,
            ],
            'meta_keys_analysis' => $metaKeyAnalysis,
            'distinct_key_diff' => [
                'total_distinct_sampled_A' => count($distinctA),
                'total_distinct_sampled_B' => count($distinctB),
                'keys_only_in_legacy'      => array_slice($diffKeysA, 0, 20),
                'keys_only_in_new'         => array_slice($diffKeysB, 0, 20),
            ],
            'sample_data'     => [
                'dbA_legacy' => $samplesA,
                'dbB_new'    => $samplesB,
            ],
            'value_mismatches_on_match_pk' => $valueMismatches,
        ];
    }

    /**
     * Ekstraksi Top, Mid, Bottom baris secara cepat & efisien
     */
    protected function extractSamples($db, string $table, ?string $sortCol, int $totalRows, int $n = 3): array
    {
        if ($totalRows === 0) {
            return ['top' => [], 'mid' => [], 'bottom' => []];
        }

        // Top N (Index ASC)
        $builderTop = $db->table($table);
        if ($sortCol) {
            $builderTop->orderBy($sortCol, 'ASC');
        }
        $top = $builderTop->limit($n, 0)->get()->getResultArray();

        // Mid N (Fast index seek for massive tables like article with 400k+ rows)
        $midOffset = max(0, (int) floor(($totalRows - $n) / 2));
        if ($totalRows > 5000 && $sortCol) {
            $midSeek = $db->table($table)->select($sortCol)->orderBy($sortCol, 'ASC')->limit(1, $midOffset)->get()->getRowArray();
            $midVal = $midSeek[$sortCol] ?? null;
            if ($midVal !== null) {
                $mid = $db->table($table)->where("$sortCol >=", $midVal)->orderBy($sortCol, 'ASC')->limit($n)->get()->getResultArray();
            } else {
                $mid = [];
            }
        } else {
            $builderMid = $db->table($table);
            if ($sortCol) {
                $builderMid->orderBy($sortCol, 'ASC');
            }
            $mid = $builderMid->limit($n, $midOffset)->get()->getResultArray();
        }

        // Bottom N (Index DESC for instant O(1) response, reversed to maintain order)
        if ($sortCol) {
            $bottomDesc = $db->table($table)->orderBy($sortCol, 'DESC')->limit($n)->get()->getResultArray();
            $bottom = array_reverse($bottomDesc);
        } else {
            $bottomOffset = max(0, $totalRows - $n);
            $bottom = $db->table($table)->limit($n, $bottomOffset)->get()->getResultArray();
        }

        return [
            'top'    => $top,
            'mid'    => $mid,
            'bottom' => $bottom,
        ];
    }

    protected function getTableColumns($db, string $table): array
    {
        return $db->getFieldNames($table) ?: [];
    }

    protected function getPrimaryKey($db, string $table): ?string
    {
        $keys = $db->getIndexData($table);
        foreach ($keys as $key) {
            if ($key->type === 'PRIMARY') {
                return $key->fields[0] ?? null;
            }
        }
        return null;
    }
}
