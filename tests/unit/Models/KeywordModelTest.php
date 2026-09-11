<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\KeywordModel;

final class KeywordModelTest extends CIUnitTestCase
{
    protected KeywordModel $keywordModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keywordModel = new KeywordModel();
    }

    public function testSyncArticleKeywordsAndRetrieval(): void
    {
        $testArticleId = 999888;
        $tags = ['IHSG Rekor', 'Saham Bluechip', 'Dividen'];

        // 1. Sync keywords to article
        $this->keywordModel->syncArticleKeywords($testArticleId, $tags);

        // 2. Retrieve linked keywords
        $linked = $this->keywordModel->getArticleKeywords($testArticleId);
        $this->assertIsArray($linked);
        $this->assertCount(3, $linked);

        $kwTexts = array_column($linked, 'keyword');
        $this->assertContains('IHSG Rekor', $kwTexts);
        $this->assertContains('Saham Bluechip', $kwTexts);
        $this->assertContains('Dividen', $kwTexts);

        // 3. Sync with reduced set (remove 'Dividen')
        $reduced = ['IHSG Rekor', 'Saham Bluechip'];
        $this->keywordModel->syncArticleKeywords($testArticleId, $reduced);

        $afterReduction = $this->keywordModel->getArticleKeywords($testArticleId);
        $this->assertCount(2, $afterReduction);
        $afterKwTexts = array_column($afterReduction, 'keyword');
        $this->assertNotContains('Dividen', $afterKwTexts);

        // Cleanup test data
        $db = \Config\Database::connect();
        $db->table('articlekeyword')->where('articleID', $testArticleId)->delete();
    }

    public function testKeywordAutocompleteSearch(): void
    {
        // Insert a distinct keyword if not exists
        $testKw = 'UnikBursa' . time();
        $db = \Config\Database::connect();
        $db->table('keyword')->insert([
            'keyword' => $testKw,
            'slug'    => url_title($testKw, '-', true)
        ]);

        $results = $this->keywordModel->like('keyword', 'UnikBursa')->findAll();
        $this->assertNotEmpty($results);
        $this->assertSame($testKw, $results[0]['keyword']);

        // Cleanup
        $db->table('keyword')->where('keyword', $testKw)->delete();
    }
}
