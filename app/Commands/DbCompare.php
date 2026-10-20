<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DbCompare extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:compare';
    protected $description = 'Compare data quality between Legacy DB (investor) and New DB (db_investorcms)';
    protected $usage       = 'db:compare [options]';
    protected $options     = [
        '--table' => 'Specific table to compare (e.g. article, category, saham)',
        '--n'     => 'Number of sample rows (default: 3)',
        '--json'  => 'Export output directly to JSON file (path)',
    ];

    public function run(array $params)
    {
        $tableParam = CLI::getOption('table') ?: ($params[0] ?? null);
        $n = (int) (CLI::getOption('n') ?: 3);
        $jsonPath = CLI::getOption('json');

        CLI::write("=== Database Quality Comparator ===", 'yellow');
        CLI::write("Comparing Legacy DB ('investor') vs New DB ('db_investorcms')...");
        CLI::newLine();

        $comparator = new \App\Controllers\DataComparator();
        $comparisonResults = $comparator->runComparison($tableParam, $n);

        $identik = 0;
        $mismatch = 0;

        foreach ($comparisonResults as $tblA => $info) {
            $statusColor = $info['status'] === 'IDENTIK' ? 'green' : 'red';
            CLI::write(
                sprintf(
                    "%-25s <-> %-20s | Rows: %7d vs %-7d | [%s]",
                    $tblA,
                    $info['new_table'],
                    $info['row_count_A'],
                    $info['row_count_B'],
                    $info['status']
                ),
                $statusColor
            );

            if ($info['status'] === 'IDENTIK') {
                $identik++;
            } else {
                $mismatch++;
                if (!empty($info['schema_diff']['columns_only_in_legacy'])) {
                    CLI::write("   -> Col only in Legacy: " . implode(', ', $info['schema_diff']['columns_only_in_legacy']), 'light_gray');
                }
                if (!empty($info['schema_diff']['columns_only_in_new'])) {
                    CLI::write("   -> Col only in New DB: " . implode(', ', $info['schema_diff']['columns_only_in_new']), 'light_gray');
                }
                if (!empty($info['value_mismatches_on_match_pk'])) {
                    CLI::write("   -> Sample Value Diffs on matching PKs: " . count($info['value_mismatches_on_match_pk']) . " row(s)", 'light_gray');
                }
                if (!empty($info['meta_keys_analysis'])) {
                    if (!empty($info['meta_keys_analysis']['keys_only_in_new'])) {
                        CLI::write("   -> Metakeys ONLY in New DB (Pollution): " . implode(', ', $info['meta_keys_analysis']['keys_only_in_new']), 'red');
                    }
                    if (!empty($info['meta_keys_analysis']['keys_only_in_legacy'])) {
                        CLI::write("   -> Metakeys ONLY in Legacy DB: " . implode(', ', $info['meta_keys_analysis']['keys_only_in_legacy']), 'yellow');
                    }
                }
            }
        }

        CLI::newLine();
        CLI::write("Comparison Summary:", 'cyan');
        CLI::write("  Total Checked : " . count($comparisonResults));
        CLI::write("  Identik       : " . $identik, 'green');
        CLI::write("  Mismatch      : " . $mismatch, 'red');

        if ($jsonPath) {
            $dir = dirname($jsonPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            file_put_contents($jsonPath, json_encode([
                'status'               => 'success',
                'timestamp'            => date('Y-m-d H:i:s'),
                'sample_size'          => $n,
                'total_tables_checked' => count($comparisonResults),
                'data'                 => $comparisonResults
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            CLI::write("Saved output JSON to: " . $jsonPath, 'green');
        }
    }
}
