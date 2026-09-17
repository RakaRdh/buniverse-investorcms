<?php

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use App\Controllers\Post;
use App\Models\ArticleModel;

class PostControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    protected ArticleModel $articleModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articleModel = new ArticleModel();

        // Default to Admin session
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'Admin';
        $_SESSION['user_name'] = 'Raka Herdika';
        $_SESSION['user_username'] = 'raka_h';
    }

    public function testPostIndexRendersSuccessfully(): void
    {
        $result = $this->withURI('http://localhost:8080/post')
            ->controller(Post::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $body = $result->response()->getBody();
        $this->assertStringContainsString('Posts &amp; News Articles', $body);
        $this->assertStringContainsString('Download List', $body);
    }

    public function testPostIndexFilters(): void
    {
        $request = service('incomingrequest', null, false);
        $request->setGlobal('get', [
            'q'      => 'IHSG',
            'kanal'  => 'Market',
            'status' => 'Published',
        ]);

        $result = $this->withURI('http://localhost:8080/post?q=IHSG&kanal=Market&status=Published')
            ->withRequest($request)
            ->controller(Post::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Posts &amp; News Articles', $result->response()->getBody());
    }

    public function testPostNewRendersEditor(): void
    {
        $result = $this->withURI('http://localhost:8080/post/new')
            ->controller(Post::class)
            ->execute('new');

        $this->assertTrue($result->isOK());
        $body = $result->response()->getBody();
        $this->assertStringContainsString('Create New Post', $body);
        $this->assertStringContainsString('postEditorForm', $body);
    }

    public function testPostEditRendersExistingArticle(): void
    {
        $sample = $this->articleModel->first();
        if ($sample) {
            $result = $this->withURI('http://localhost:8080/post/edit/' . $sample['articleID'])
                ->controller(Post::class)
                ->execute('edit', (int)$sample['articleID']);

            $this->assertTrue($result->isOK());
            $this->assertStringContainsString('Edit Post', $result->response()->getBody());
        }
    }

    public function testPostSaveAndLifecycle(): void
    {
        $uniqueTitle = 'Controller Unit Test Article ' . time();
        $postData = [
            'title'       => $uniqueTitle,
            'content'     => '<p>Unit test lifecycle article content.</p>',
            'summary'     => 'Brief summary for unit test lifecycle article.',
            'kanal'       => 'Market',
            'sub_kanal'   => 'Stock',
            'status'      => 'Draft',
            'author'      => 'Raka Herdika',
            'is_premium'  => 0,
            'source'      => 'Investor.id / Riset',
            'tags'        => 'Saham, BBCA, Dividen',
            'point_1'     => 'Key point 1',
            'point_2'     => 'Key point 2',
        ];

        $request = service('incomingrequest', null, false);
        $request->setGlobal('post', $postData);

        // 1. Create Article via Save
        $saveResult = $this->withURI('http://localhost:8080/post/save')
            ->withRequest($request)
            ->controller(Post::class)
            ->execute('save');

        $this->assertTrue($saveResult->isRedirect());

        // Find created article
        $created = $this->articleModel->where('headline', $uniqueTitle)->first();
        $this->assertNotNull($created);
        $createdId = (int)$created['articleID'];

        // 2. Update Article via Update action
        $updateData = $postData;
        $updateData['title'] = $uniqueTitle . ' [Updated]';
        $updateData['status'] = 'Published';

        $updateRequest = service('incomingrequest', null, false);
        $updateRequest->setGlobal('post', $updateData);

        $updateResult = $this->withURI('http://localhost:8080/post/update/' . $createdId)
            ->withRequest($updateRequest)
            ->controller(Post::class)
            ->execute('update', $createdId);

        $this->assertTrue($updateResult->isRedirect());

        // Verify updated
        $updated = $this->articleModel->find($createdId);
        $this->assertSame($uniqueTitle . ' [Updated]', $updated['headline']);

        // 3. Delete Article as Admin
        $deleteResult = $this->withURI('http://localhost:8080/post/delete/' . $createdId)
            ->controller(Post::class)
            ->execute('delete', $createdId);

        $this->assertTrue($deleteResult->isRedirect());
        $this->assertNull($this->articleModel->find($createdId));
    }

    public function testPostRbacAuthorCannotDelete(): void
    {
        // Switch to Author role
        $_SESSION['user_role'] = 'Author';
        $_SESSION['user_id'] = 3;

        // Dynamically find an active article to test RBAC protection
        $existing = $this->articleModel->where('status !=', 'Deleted')->first();
        $targetId = $existing ? (int)$existing['articleID'] : 1;

        $deleteResult = $this->withURI('http://localhost:8080/post/delete/' . $targetId)
            ->controller(Post::class)
            ->execute('delete', $targetId);

        $this->assertTrue($deleteResult->isRedirect());
        // Verify flashdata or redirected without deleting article
        $sample = $this->articleModel->find($targetId);
        $this->assertNotNull($sample);
    }

    public function testPostBulkActionEndpointIsRemoved(): void
    {
        // SEC-06: Verifikasi bahwa ghost endpoint bulkAction telah dihapus dari Post controller
        $this->assertFalse(method_exists(Post::class, 'bulkAction'));
    }

    public function testPostSearchTagsReturnsJsonResponse(): void
    {
        $request = service('incomingrequest', null, false);
        $request->setGlobal('get', ['q' => 'Saham']);

        $result = $this->withURI('http://localhost:8080/api/tags/search?q=Saham')
            ->withRequest($request)
            ->controller(Post::class)
            ->execute('searchTags');

        $this->assertTrue($result->isOK());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertIsArray($body);
    }
}
