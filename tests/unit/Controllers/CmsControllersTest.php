<?php

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use App\Controllers\Auth;
use App\Controllers\Dashboard;
use App\Controllers\Kanal;
use App\Controllers\Headline;
use App\Controllers\Media;
use App\Controllers\Files;
use App\Controllers\Member;
use App\Controllers\User;
use App\Controllers\Apps;
use App\Controllers\EmbedChart;
use App\Controllers\Statistic;
use App\Controllers\StatisticReport;
use App\Controllers\PostPreview;
use App\Models\CategoryModel;
use App\Models\MemberModel;

class CmsControllersTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup session for authenticated controller routes
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'Admin';
        $_SESSION['user_name'] = 'Raka Herdika';
        $_SESSION['user_username'] = 'raka_h';
    }

    public function testAuthLoginViewAndLogout(): void
    {
        // 1. Logout
        $logoutResult = $this->withURI('http://localhost:8080/logout')
            ->controller(Auth::class)
            ->execute('logout');
        $this->assertTrue($logoutResult->isRedirect());

        // Clear session to test login view
        $_SESSION = [];

        // 2. Login GET view
        $loginResult = $this->withURI('http://localhost:8080/login')
            ->controller(Auth::class)
            ->execute('login');
        $this->assertTrue($loginResult->isOK());
        $this->assertStringContainsString('Login', $loginResult->response()->getBody());
    }

    public function testDashboardIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/dashboard')
            ->controller(Dashboard::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $body = $result->response()->getBody();
        $this->assertStringContainsString('Newsroom Overview', $body);
        $this->assertStringContainsString('Dashboard', $body);
    }

    public function testKanalMasterAndSubKanal(): void
    {
        // Master kanal view
        $masterResult = $this->withURI('http://localhost:8080/kanal/master')
            ->controller(Kanal::class)
            ->execute('master');
        $this->assertTrue($masterResult->isOK());
        $this->assertStringContainsString('Master Kanal Management', $masterResult->response()->getBody());

        // Sub kanal view
        $subResult = $this->withURI('http://localhost:8080/kanal/sub')
            ->controller(Kanal::class)
            ->execute('subKanal');
        $this->assertTrue($subResult->isOK());
        $this->assertStringContainsString('Sub Kanal Management', $subResult->response()->getBody());

        // Stats view
        $statsResult = $this->withURI('http://localhost:8080/kanal/stats')
            ->controller(Kanal::class)
            ->execute('stats');
        $this->assertTrue($statsResult->isOK());
        $this->assertStringContainsString('Performa Kanal', $statsResult->response()->getBody());
    }

    public function testHeadlineIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/headline')
            ->controller(Headline::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Headline Management', $result->response()->getBody());
    }

    public function testMediaIndexAndNew(): void
    {
        $indexResult = $this->withURI('http://localhost:8080/media')
            ->controller(Media::class)
            ->execute('index');
        $this->assertTrue($indexResult->isOK());
        $this->assertStringContainsString('Media Management', $indexResult->response()->getBody());

        $newResult = $this->withURI('http://localhost:8080/media/new')
            ->controller(Media::class)
            ->execute('new');
        $this->assertTrue($newResult->isOK());
        $this->assertStringContainsString('Upload Media', $newResult->response()->getBody());
    }

    public function testFilesIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/files')
            ->controller(Files::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Files Management', $result->response()->getBody());
    }

    public function testMemberIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/member')
            ->controller(Member::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Direktori Member', $result->response()->getBody());
    }

    public function testUserIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/user')
            ->controller(User::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('User Management', $result->response()->getBody());
    }

    public function testAppsVersionAndPush(): void
    {
        $versionResult = $this->withURI('http://localhost:8080/apps-version')
            ->controller(Apps::class)
            ->execute('version');
        $this->assertTrue($versionResult->isOK());
        $this->assertStringContainsString('Apps — Version Management', $versionResult->response()->getBody());
    }

    public function testEmbedChartIndex(): void
    {
        $result = $this->withURI('http://localhost:8080/embed-chart')
            ->controller(EmbedChart::class)
            ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('Financial Embed Charts', $result->response()->getBody());
    }

    public function testStatisticIndexAndReport(): void
    {
        $statResult = $this->withURI('http://localhost:8080/statistic')
            ->controller(Statistic::class)
            ->execute('index');
        $this->assertTrue($statResult->isOK());
        $this->assertStringContainsString('Audience Analytics', $statResult->response()->getBody());

        $reportResult = $this->withURI('http://localhost:8080/statistic-report')
            ->controller(StatisticReport::class)
            ->execute('index');
        $this->assertTrue($reportResult->isOK());
        $this->assertStringContainsString('Editorial Performance', $reportResult->response()->getBody());
    }

    public function testPostPreviewShowValidAndInvalid(): void
    {
        // 1. Invalid article id redirects to /post
        $invalidResult = $this->withURI('http://localhost:8080/post/preview/99999999')
            ->controller(PostPreview::class)
            ->execute('show', 99999999);
        $this->assertTrue($invalidResult->isRedirect());

        // 2. Valid article id renders preview
        $db = \Config\Database::connect();
        $sample = $db->table('entity_nano')->where('status_code', 'Published')->get()->getRowArray();
        if ($sample) {
            $validResult = $this->withURI('http://localhost:8080/post/preview/' . $sample['ref_entity_nano_id'])
                ->controller(PostPreview::class)
                ->execute('show', (int)$sample['ref_entity_nano_id']);
            $this->assertTrue($validResult->isOK());
        }
    }
}
