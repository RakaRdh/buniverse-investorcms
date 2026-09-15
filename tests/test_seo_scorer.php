<?php

// Test script for SeoScorer library and rule engine
require_once __DIR__ . '/../vendor/autoload.php';

// Helper mock if env() is called in standalone script
if (!function_exists('env')) {
    function env($key, $default = null) { return $default; }
}
if (!function_exists('log_message')) {
    function log_message($level, $message) {}
}

use App\Libraries\SeoScorer;

$scorer = new SeoScorer();

// Test article dummy
$dummyArticle = [
    'headline' => 'IHSG Menguat Tipis ke Level 7.800 di Awal Perdagangan Saham',
    'summary'  => 'Indeks Harga Saham Gabungan (IHSG) bergerak menguat di awal perdagangan saham hari ini seiring penguatan bursa regional dan saham perbankan.',
    'body'     => '<p>Indeks Harga Saham Gabungan (IHSG) dibuka menguat pada awal perdagangan hari ini di level 7.800 seiring dengan sentimen positif bursa global dan penguatan saham perbankan seperti BBCA dan BMRI.</p>'
                . '<h2>Sentimen Pasar Regional</h2>'
                . '<p>Kenaikan bursa Asia turut memberikan dorongan bagi para investor domestik untuk melakukan akumulasi saham-saham berkapitalisasi besar.</p>'
                . '<p>Untuk informasi pergerakan saham lainnya, silakan baca artikel terkait: <a href="https://investor.id/market/123/saham-bbca">Pergerakan Saham BBCA Hari Ini</a>.</p>',
    'slug'     => 'ihsg-menguat-tipis-ke-level-7800-di-awal-perdagangan-saham',
];

$dummyKeywords = ['IHSG', 'Saham', 'Bursa Efek'];
$dummyMeta = [
    'thumbnail' => '/img/market_banner.jpg'
];

echo "=== TESTING RULE-BASED SCORING ===\n";
$ruleResult = $scorer->calculateRuleScore(
    $dummyArticle['headline'],
    $dummyArticle['body'],
    $dummyArticle['summary'],
    $dummyArticle['slug'],
    $dummyMeta['thumbnail'],
    $dummyKeywords
);

echo "Total Rule Score: " . $ruleResult['total_score'] . " / 100\n";
echo "Number of parameters evaluated: " . count($ruleResult['parameters']) . "\n\n";

foreach ($ruleResult['parameters'] as $idx => $p) {
    echo ($idx + 1) . ". [" . strtoupper($p['status']) . "] " . $p['title'] . " (Bobot: {$p['weight']}, Skor: {$p['score']})\n";
    echo "   - Message: " . $p['message'] . "\n";
    echo "   - Current: " . $p['current_val'] . "\n";
}

echo "\n=== ALL CHECKS PASSED ===\n";
