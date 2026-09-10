<?php

namespace Tests\Unit\Models;

use App\Models\ArticleModel;
use CodeIgniter\Test\CIUnitTestCase;

final class ArticleModelTest extends CIUnitTestCase
{
    protected ArticleModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArticleModel();
    }

    public function testGetArticlesWithRelationsReturnsDataAndTotal(): void
    {
        $result = $this->model->getArticlesWithRelations([], 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertGreaterThan(0, $result['total']);
        $this->assertLessThanOrEqual(10, count($result['data']));
    }

    public function testGetArticlesWithCategoryFilter(): void
    {
        $result = $this->model->getArticlesWithRelations(['kanal' => 'Market'], 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        foreach ($result['data'] as $article) {
            $this->assertStringContainsStringIgnoringCase('Market', $article['category']);
        }
    }

    public function testGetArticlesWithStatusFilter(): void
    {
        $result = $this->model->getArticlesWithRelations(['status' => 'Publish'], 1, 10);
        $this->assertIsArray($result);
        foreach ($result['data'] as $article) {
            $this->assertSame('Publish', $article['status']);
        }
    }

    public function testGetArticleDetailReturnsCompleteRelations(): void
    {
        // First get an existing ID
        $list = $this->model->getArticlesWithRelations([], 1, 1);
        $this->assertNotEmpty($list['data']);
        $targetId = (int)$list['data'][0]['id'];

        $detail = $this->model->getArticleDetail($targetId);
        $this->assertNotNull($detail);
        $this->assertSame($targetId, (int)$detail['id']);
        $this->assertArrayHasKey('title', $detail);
        $this->assertArrayHasKey('content', $detail);
        $this->assertArrayHasKey('tags', $detail);
        $this->assertArrayHasKey('points', $detail);
    }

    public function testGetArticleDetailWithInvalidIdReturnsNull(): void
    {
        $this->assertNull($this->model->getArticleDetail(999999));
    }

    public function testCreateAndUpdateAndDeleteArticleCycle(): void
    {
        $testTitle = 'Unit Test Article ' . time();
        $data = [
            'title'             => $testTitle,
            'slug'              => url_title($testTitle, '-', true),
            'summary'           => 'Ringkasan artikel unit test.',
            'content'           => '<p>Konten artikel pengujian unit test CI4.</p>',
            'status'            => 'Draft',
            'publish_date'      => date('Y-m-d H:i:s'),
            'thumbnail'         => '/img/investor_placeholder.jpg',
            'upper_deck'        => 'FOKUS EKONOMI',
            'source'            => 'Investor.id / Riset',
            'kanal'             => 'Market',
            'sub_kanal'         => 'Stock',
            'is_premium'        => 0,
            'headline_type'     => 'none',
            'ref_entity_acc_id' => 1,
        ];

        // 1. Create
        $newId = $this->model->createArticle($data, 1, 1, ['saham', 'ihsg'], ['Poin penting 1']);
        $this->assertGreaterThan(0, $newId);

        // Verify created
        $fetched = $this->model->getArticleDetail($newId);
        $this->assertNotNull($fetched);
        $this->assertSame($testTitle, $fetched['title']);

        // 2. Update
        $updatedTitle = $testTitle . ' - Edited';
        $data['title'] = $updatedTitle;
        $this->model->updateArticle($newId, $data, 1, 1, ['saham', 'idx'], ['Poin 1 updated'], 1);

        $fetchedAfter = $this->model->getArticleDetail($newId);
        $this->assertSame($updatedTitle, $fetchedAfter['title']);

        // 3. Delete
        $this->model->deleteArticle($newId);
        $deletedCheck = $this->model->getArticleDetail($newId);
        $this->assertNull($deletedCheck);
    }

    public function testGetDashboardMetricsReturnsCategorizedTotals(): void
    {
        $stats = $this->model->getDashboardMetrics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('todayArticles', $stats);
        $this->assertArrayHasKey('published', $stats);
        $this->assertArrayHasKey('drafts', $stats);
        $this->assertArrayHasKey('scheduled', $stats);
        $this->assertArrayHasKey('review', $stats);
    }

    public function testGetTopArticlesReturnsOrderedResults(): void
    {
        $popular = $this->model->getTopArticles(5);
        $this->assertIsArray($popular);
        $this->assertLessThanOrEqual(5, count($popular));
        if (count($popular) >= 2) {
            $this->assertGreaterThanOrEqual((int)$popular[1]['views'], (int)$popular[0]['views']);
        }
    }

    public function testGetPublishingChartDataReturnsDynamicStructure(): void
    {
        $chartData = $this->model->getPublishingChartData();
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('today', $chartData);
        $this->assertArrayHasKey('7d', $chartData);
        $this->assertArrayHasKey('30d', $chartData);

        // Verify today series has data
        $this->assertCount(2, $chartData['today']['series']);
        $this->assertSame('Published Articles', $chartData['today']['series'][0]['name']);
        $this->assertSame('Drafts & Reviews', $chartData['today']['series'][1]['name']);
    }

    public function testGetTrafficChartDataReturnsDynamicStructure(): void
    {
        $traffic = $this->model->getTrafficChartData();
        $this->assertIsArray($traffic);
        $this->assertArrayHasKey('today', $traffic);
        $this->assertArrayHasKey('7d', $traffic);
        $this->assertArrayHasKey('mtd', $traffic);

        $this->assertCount(2, $traffic['today']['series']);
        $this->assertSame('Direct & App Visitors', $traffic['today']['series'][0]['name']);
    }
}
