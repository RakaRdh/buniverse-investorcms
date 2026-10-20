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
        $tags = ['IHSG Rekor Unik', 'Saham Bluechip Unik', 'Dividen Saham Unik'];

        // 1. Sync keywords to article
        $this->keywordModel->syncArticleKeywords($testArticleId, $tags);

        // 2. Retrieve linked keywords
        $linked = $this->keywordModel->getArticleKeywords($testArticleId);
        $this->assertIsArray($linked);
        $this->assertCount(3, $linked);

        $kwTexts = array_column($linked, 'keyword');
        $this->assertContains('IHSG Rekor Unik', $kwTexts);
        $this->assertContains('Saham Bluechip Unik', $kwTexts);
        $this->assertContains('Dividen Saham Unik', $kwTexts);

        // 3. Sync with reduced set (remove 'Dividen Saham Unik')
        $reduced = ['IHSG Rekor Unik', 'Saham Bluechip Unik'];
        $this->keywordModel->syncArticleKeywords($testArticleId, $reduced);

        $afterReduction = $this->keywordModel->getArticleKeywords($testArticleId);
        $this->assertCount(2, $afterReduction);
        $afterKwTexts = array_column($afterReduction, 'keyword');
        $this->assertNotContains('Dividen Saham Unik', $afterKwTexts);

        // Cleanup test data
        $db = \Config\Database::connect();
        $db->table('ArticleKeyword')->where('articleID', $testArticleId)->delete();
    }

    public function testKeywordAutocompleteSearch(): void
    {
        // Insert a distinct keyword if not exists
        $testKw = 'UnikBursa' . time();
        $db = \Config\Database::connect();
        $db->table('Keyword')->insert([
            'keyword' => $testKw,
            'slug'    => url_title($testKw, '-', true)
        ]);

        $results = $this->keywordModel->like('keyword', 'UnikBursa')->findAll();
        $this->assertNotEmpty($results);
        $this->assertSame($testKw, $results[0]['keyword']);

        // Cleanup
        $db->table('Keyword')->where('keyword', $testKw)->delete();
    }
}
