<?php

namespace Tests\Unit\Libraries;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\SeoScorer;

class SeoScorerTest extends CIUnitTestCase
{
    protected SeoScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new SeoScorer();
    }

    public function testOnlyFirstKeywordIsUsedForScoring()
    {
        $headline = 'Saham BBCA Menguat Signifikan di Sesi Perdagangan Pagi Ini';
        $summary = 'Saham BBCA mencatat penguatan tajam terdorong aksi beli investor asing dengan volume transaksi tinggi hari ini.';
        $body = '<p>Saham BBCA menjadi penggerak utama indeks bursa hari ini dengan transaksi yang sangat aktif dan volume solid di pasar reguler.</p>' .
                '<p>Kinerja emiten sektor keuangan ini didukung oleh capaian laba bersih kuartal ketiga yang bertumbuh pesat melampaui estimasi konsensus analis pasar modal.</p>' .
                '<p>Manajemen perseroan optimistis pertumbuhan kredit korporasi dan konsumer akan terus terjaga stabil hingga akhir tahun dengan rasio kredit bermasalah yang terkendali dengan sangat baik.</p>' .
                '<p>Di sisi lain, investor institusi asing terus mencatatkan aksi beli bersih (net buy) seiring dengan prospek pemulihan ekonomi nasional dan penguatan nilai tukar rupiah.</p>';
        $slug = 'saham-bbca-menguat-signifikan';
        $thumbnail = '/img/sample.jpg';

        // Keyword pertama: 'Saham BBCA' (ada di judul, summary, first p)
        // Keyword kedua: 'IHSG' (tidak ada di judul)
        // Keyword ketiga: 'Dividen'
        $keywords = ['Saham BBCA', 'IHSG', 'Dividen'];

        $result = $this->scorer->calculateRuleScore($headline, $body, $summary, $slug, $thumbnail, $keywords);

        $params = [];
        foreach ($result['parameters'] as $p) {
            $params[$p['key']] = $p;
        }

        // Parameter 2: Keyword in title harus PASS karena keyword pertama 'Saham BBCA' ada
        $this->assertEquals('pass', $params['keyword_in_title']['status']);
        $this->assertEquals(10, $params['keyword_in_title']['score']);

        // Parameter 4: Keyword in summary harus PASS
        $this->assertEquals('pass', $params['keyword_in_summary']['status']);
        $this->assertEquals(5, $params['keyword_in_summary']['score']);

        // Parameter 6: Keyword in first p harus PASS
        $this->assertEquals('pass', $params['keyword_in_first_p']['status']);
        $this->assertEquals(10, $params['keyword_in_first_p']['score']);

        // Parameter 12: Keyword density (3 keywords) harus PASS (<= 3 keywords)
        $this->assertEquals('pass', $params['keyword_density']['status']);
    }

    public function testMoreThanThreeKeywordsIsMarkedAsSpam()
    {
        $headline = 'Saham BBCA Menguat Signifikan di Sesi Perdagangan Pagi Ini';
        $summary = 'Saham BBCA mencatat penguatan tajam terdorong aksi beli investor asing dengan volume transaksi tinggi hari ini.';
        $body = '<p>Saham BBCA menjadi penggerak utama indeks bursa hari ini.</p>';
        $slug = 'saham-bbca-menguat-signifikan';
        $thumbnail = '/img/sample.jpg';

        // 4 keywords: melebihi batas maksimal 3
        $keywords = ['Saham BBCA', 'IHSG', 'Dividen', 'Perbankan'];

        $result = $this->scorer->calculateRuleScore($headline, $body, $summary, $slug, $thumbnail, $keywords);

        $params = [];
        foreach ($result['parameters'] as $p) {
            $params[$p['key']] = $p;
        }

        // Parameter 12 harus FAIL dan dicap SPAM
        $this->assertEquals('fail', $params['keyword_density']['status']);
        $this->assertStringContainsString('SPAM', $params['keyword_density']['message']);
        $this->assertStringContainsString('melebihi batas maksimal 3', $params['keyword_density']['message']);
    }

    public function testSecondKeywordDoesNotTriggerPassIfFirstKeywordIsMissing()
    {
        // Judul mengandung 'IHSG' tetapi TIDAK mengandung 'Saham BBRI'
        $headline = 'Laju IHSG Menguat Signifikan di Sesi Perdagangan Pagi Ini';
        $summary = 'Ringkasan artikel pasar modal hari ini di bursa efek indonesia.';
        $body = '<p>Perdagangan bursa bergerak positif didorong sentimen regional.</p>';
        $slug = 'laju-ihsg-menguat-signifikan';
        $thumbnail = '/img/sample.jpg';

        // Keyword pertama 'Saham BBRI', keyword kedua 'IHSG'
        // Karena hanya keyword pertama yang dipakai, maka keyword_in_title harus FAIL!
        $keywords = ['Saham BBRI', 'IHSG'];

        $result = $this->scorer->calculateRuleScore($headline, $body, $summary, $slug, $thumbnail, $keywords);

        $params = [];
        foreach ($result['parameters'] as $p) {
            $params[$p['key']] = $p;
        }

        $this->assertEquals('fail', $params['keyword_in_title']['status']);
        $this->assertEquals(0, $params['keyword_in_title']['score']);
    }
}
