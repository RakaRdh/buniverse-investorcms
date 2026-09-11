<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\MediaModel;

final class MediaModelTest extends CIUnitTestCase
{
    protected MediaModel $mediaModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaModel = new MediaModel();
    }

    public function testGetMediaWithRelationsPaginationAndSearch(): void
    {
        // 1. First page
        $result = $this->mediaModel->getMediaWithRelations(1, 5);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertLessThanOrEqual(5, count($result['data']));

        if (!empty($result['data'])) {
            $item = $result['data'][0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('image', $item);
            $this->assertArrayHasKey('usage_count', $item);
        }

        // 2. Search nonexistent keyword
        $search = $this->mediaModel->getMediaWithRelations(1, 5, 'nonexistent_media_keyword_xyz_999');
        $this->assertSame(0, $search['total']);
    }

    public function testGetArticlesUsingMediaReturnsArticlesOrEmpty(): void
    {
        $all = $this->mediaModel->findAll();
        if (!empty($all)) {
            $mediaId = (int)$all[0]['mediaID'];
            $articles = $this->mediaModel->getArticlesUsingMedia($mediaId);
            $this->assertIsArray($articles);

            foreach ($articles as $article) {
                $this->assertArrayHasKey('id', $article);
                $this->assertArrayHasKey('title', $article);
                $this->assertArrayHasKey('status', $article);
            }
        }

        // Invalid media ID returns empty array
        $invalid = $this->mediaModel->getArticlesUsingMedia(99999999);
        $this->assertIsArray($invalid);
        $this->assertEmpty($invalid);
    }
}
