<?php

namespace Tests\Unit\Models;

use App\Models\AuthorModel;
use CodeIgniter\Test\CIUnitTestCase;

final class AuthorModelTest extends CIUnitTestCase
{
    protected AuthorModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AuthorModel();
    }

    public function testGetAuthorsWithCountReturnsAuthors(): void
    {
        $authors = $this->model->getAuthorsWithCount();
        $this->assertIsArray($authors);
        $this->assertNotEmpty($authors);
        $this->assertArrayHasKey('name', $authors[0]);
        $this->assertArrayHasKey('article_count', $authors[0]);
    }

    public function testFindOrCreateReturnsExistingAuthorId(): void
    {
        $existing = $this->model->first();
        if ($existing) {
            $id = $this->model->findOrCreate($existing['aubert_name_a']);
            $this->assertSame((int)$existing['ref_entity_aubert_id'], $id);
        }
    }

    public function testFindOrCreateCreatesNewAuthor(): void
    {
        $uniqueName = 'Author Tester ' . time();
        $id = $this->model->findOrCreate($uniqueName);
        $this->assertGreaterThan(0, $id);

        $saved = $this->model->find($id);
        $this->assertNotNull($saved);
        $this->assertSame($uniqueName, $saved['aubert_name_a']);

        // Clean up
        $this->model->delete($id);
    }
}
