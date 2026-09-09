<?php

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use App\Controllers\Api\MarketApi;
use App\Controllers\Api\AuthApi;
use App\Controllers\Api\ArticleApi;
use App\Controllers\Api\PostApi;
use App\Controllers\AiAssistant;

class ApiControllersTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testMarketApiTicker(): void
    {
        $result = $this->withURI('http://localhost:8080/api/market/ticker')
            ->controller(MarketApi::class)
            ->execute('ticker');

        $this->assertTrue($result->isOK());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
        $this->assertArrayHasKey('symbol', $body['data'][0]);
    }

    public function testMarketApiMovers(): void
    {
        $result = $this->withURI('http://localhost:8080/api/market/movers')
            ->controller(MarketApi::class)
            ->execute('movers');

        $this->assertTrue($result->isOK());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertArrayHasKey('gainers', $body['data']);
        $this->assertArrayHasKey('losers', $body['data']);
        $this->assertNotEmpty($body['data']['gainers']);
        $this->assertNotEmpty($body['data']['losers']);
    }

    public function testAuthApiClientRegisterValidationFail(): void
    {
        $result = $this->withURI('http://localhost:8080/api/auth/client-register')
            ->controller(AuthApi::class)
            ->execute('clientRegister');

        $this->assertSame(400, $result->response()->getStatusCode());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('error', $body['status']);
    }

    public function testAuthApiClientLoginValidationFail(): void
    {
        $result = $this->withURI('http://localhost:8080/api/auth/client-login')
            ->controller(AuthApi::class)
            ->execute('clientLogin');

        $this->assertSame(400, $result->response()->getStatusCode());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('error', $body['status']);
    }

    public function testAuthApiClientLoginInvalidUser(): void
    {
        $payload = json_encode([
            'email'    => 'nonexistent_test_reader@example.com',
            'password' => 'wrongpass'
        ]);

        $result = $this->withURI('http://localhost:8080/api/auth/client-login')
            ->withBody($payload)
            ->controller(AuthApi::class)
            ->execute('clientLogin');

        $this->assertSame(401, $result->response()->getStatusCode());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('error', $body['status']);
        $this->assertSame('Akun member tidak ditemukan.', $body['message']);
    }

    public function testArticleApiIndex(): void
    {
        $request = service('incomingrequest', null, false);
        $request->setGlobal('get', ['limit' => 5, 'offset' => 0]);

        $result = $this->withURI('http://localhost:8080/api/articles?limit=5&offset=0')
            ->withRequest($request)
            ->controller(ArticleApi::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('meta', $body);
        $this->assertSame(5, $body['meta']['limit']);
    }

    public function testPostApiAutosaveEmptyDraft(): void
    {
        $result = $this->withURI('http://localhost:8080/api/autosave')
            ->controller(PostApi::class)
            ->execute('autosave');

        $body = json_decode($result->response()->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Naskah masih kosong', $body['message']);
    }

    public function testPostApiAutosaveValidDraft(): void
    {
        $payload = json_encode([
            'title'   => 'Unit Test Autosave Article ' . time(),
            'content' => '<p>Draft content for unit testing.</p>',
            'kanal'   => 'Market'
        ]);

        $result = $this->withURI('http://localhost:8080/api/autosave')
            ->withBody($payload)
            ->controller(PostApi::class)
            ->execute('autosave');

        $this->assertTrue($result->isOK());
        $body = json_decode($result->response()->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertNotEmpty($body['id']);

        // Clean up created draft via ArticleModel
        $articleModel = new \App\Models\ArticleModel();
        $articleModel->deleteArticle((int)$body['id']);
    }

    public function testAiAssistantValidation(): void
    {
        // 1. Title without body
        $result1 = $this->withURI('http://localhost:8080/ai/generate-title')
            ->controller(AiAssistant::class)
            ->execute('generateTitle');
        $this->assertSame(400, $result1->response()->getStatusCode());

        // 2. Summary without title or body
        $result2 = $this->withURI('http://localhost:8080/ai/generate-summary')
            ->controller(AiAssistant::class)
            ->execute('generateSummary');
        $this->assertSame(400, $result2->response()->getStatusCode());

        // 3. Scrape without url
        $result3 = $this->withURI('http://localhost:8080/ai/scrape')
            ->controller(AiAssistant::class)
            ->execute('scrape');
        $this->assertSame(400, $result3->response()->getStatusCode());
    }
}
