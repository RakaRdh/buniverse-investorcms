<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\HeadlineModel;
use App\Models\ArticleModel;

final class HeadlineModelTest extends CIUnitTestCase
{
    protected HeadlineModel $headlineModel;
    protected ArticleModel $articleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->headlineModel = new HeadlineModel();
        $this->articleModel = new ArticleModel();
    }

    public function testGetActiveHeadlinesAllAndSpecificCategory(): void
    {
        // 1. All categories
        $all = $this->headlineModel->getActiveHeadlines('all');
        $this->assertIsArray($all);

        // 2. Specific 'home' category
        $homeHeadlines = $this->headlineModel->getActiveHeadlines('home');
        $this->assertIsArray($homeHeadlines);

        // Verify ordering is strictly ascending by num
        for ($i = 0; $i < count($homeHeadlines) - 1; $i++) {
            $this->assertLessThanOrEqual(
                (int)$homeHeadlines[$i + 1]['headline_position'],
                (int)$homeHeadlines[$i]['headline_position']
            );
        }
    }

    public function testBatchReorderPositions(): void
    {
        $homeHeadlines = $this->headlineModel->getActiveHeadlines('home');
        if (count($homeHeadlines) >= 2) {
            $id1 = (int)$homeHeadlines[0]['id'];
            $id2 = (int)$homeHeadlines[1]['id'];

            // Swap order: [id2, id1]
            $reordered = [$id2, $id1];
            $status = $this->headlineModel->batchReorder('home', $reordered);
            $this->assertTrue($status);

            // Re-fetch and verify new positions
            $refetched = $this->headlineModel->getActiveHeadlines('home');
            $map = [];
            foreach ($refetched as $row) {
                $map[(int)$row['id']] = (int)$row['headline_position'];
            }

            $this->assertSame(1, $map[$id2]);
            $this->assertSame(2, $map[$id1]);

            // Restore original order
            $restore = array($id1, $id2);
            $this->headlineModel->batchReorder('home', $restore);
        } else {
            $this->markTestSkipped('Not enough home headlines to test reordering.');
        }
    }

    public function testBatchReorderWithEmptyArrayReturnsFalse(): void
    {
        $status = $this->headlineModel->batchReorder('home', []);
        $this->assertFalse($status);
    }
}
