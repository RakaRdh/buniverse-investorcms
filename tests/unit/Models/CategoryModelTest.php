<?php

namespace Tests\Unit\Models;

use App\Models\CategoryModel;
use CodeIgniter\Test\CIUnitTestCase;

final class CategoryModelTest extends CIUnitTestCase
{
    protected CategoryModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new CategoryModel();
    }

    public function testGetKanalTreeReturnsParentsWithSubKanals(): void
    {
        $tree = $this->model->getKanalTree();
        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
        $first = $tree[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('sub_kanals', $first);
        $this->assertIsArray($first['sub_kanals']);
    }

    public function testGetSubKanalsListReturnsFlatList(): void
    {
        $subList = $this->model->getSubKanalsList();
        $this->assertIsArray($subList);
        if (!empty($subList)) {
            $this->assertArrayHasKey('parent_name', $subList[0]);
            $this->assertArrayHasKey('name', $subList[0]);
        }
    }

    public function testGetKanalPerformanceReturnsMetrics(): void
    {
        $perf = $this->model->getKanalPerformance();
        $this->assertIsArray($perf);
        $this->assertNotEmpty($perf);
        $this->assertArrayHasKey('total_articles', $perf[0]);
        $this->assertArrayHasKey('total_views', $perf[0]);
    }
}
