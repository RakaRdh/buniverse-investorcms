<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MediaModel;
use App\Models\FileModel;
use App\Models\HeadlineModel;
use App\Models\MemberModel;
use App\Models\NotificationModel;
use App\Models\FeatureContentModel;
use App\Models\ChartModel;
use App\Models\TagModel;
use App\Models\ActivityLogModel;
use App\Models\DataRepository;

class AdditionalModelsTest extends CIUnitTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
    }

    public function testMediaModelGetMediaWithRelations(): void
    {
        $mediaModel = new MediaModel();
        $result = $mediaModel->getMediaWithRelations(1, 5, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsInt($result['total']);

        if (!empty($result['data'])) {
            $item = $result['data'][0];
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('image', $item);
            $this->assertArrayHasKey('usage_count', $item);
        }

        // Test with search keyword
        $searchResult = $mediaModel->getMediaWithRelations(1, 5, 'nonexistentkeywordxyz123');
        $this->assertIsArray($searchResult);
        $this->assertSame(0, $searchResult['total']);
    }

    public function testFileModelGetFilesWithUploader(): void
    {
        $fileModel = new FileModel();
        $result = $fileModel->getFilesWithUploader(null, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('totalItems', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertSame(1, $result['page']);
        $this->assertSame(10, $result['perPage']);

        // Test keyword search
        $searchResult = $fileModel->getFilesWithUploader('nonexistentxyz999', 1, 10);
        $this->assertIsArray($searchResult);
        $this->assertSame(0, $searchResult['totalItems']);
    }

    public function testHeadlineModelGetActiveHeadlines(): void
    {
        $headlineModel = new HeadlineModel();
        $allHeadlines = $headlineModel->getActiveHeadlines('all');
        $this->assertIsArray($allHeadlines);

        $specificHeadlines = $headlineModel->getActiveHeadlines('Market');
        $this->assertIsArray($specificHeadlines);
    }

    public function testMemberModelCrudAndStats(): void
    {
        $memberModel = new MemberModel();

        // 1. Stats
        $stats = $memberModel->getMemberStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('suspended', $stats);

        // 2. Status code and label mapping
        $this->assertSame('Active', MemberModel::getStatusLabel('STATUS_1'));
        $this->assertSame('Pending', MemberModel::getStatusLabel('STATUS_2'));
        $this->assertSame('Suspended', MemberModel::getStatusLabel('STATUS_3'));
        $this->assertSame('STATUS_1', MemberModel::getStatusCode('active'));
        $this->assertSame('STATUS_2', MemberModel::getStatusCode('pending'));
        $this->assertSame('STATUS_3', MemberModel::getStatusCode('suspended'));

        // 3. Create Member
        $uniqueEmail = 'testmember_' . time() . '@example.com';
        $newId = $memberModel->saveMember([
            'full_name' => 'Unit Test Member',
            'email'     => $uniqueEmail,
            'company'   => 'Test Lab Corp',
            'status'    => 'Active',
            'password'  => 'secret123',
        ]);

        $this->assertIsInt($newId);
        $this->assertGreaterThan(0, $newId);

        // 4. Read Members with filter
        $list = $memberModel->getMembers(['q' => $uniqueEmail]);
        $this->assertGreaterThanOrEqual(1, $list['totalItems']);
        $this->assertSame('Unit Test Member', $list['data'][0]['full_name']);

        // 5. Update Member
        $memberModel->saveMember([
            'full_name' => 'Updated Test Member',
            'email'     => $uniqueEmail,
            'company'   => 'Updated Corp',
            'status'    => 'Suspended',
        ], $newId);

        $updatedList = $memberModel->getMembers(['q' => $uniqueEmail]);
        $this->assertSame('Updated Test Member', $updatedList['data'][0]['full_name']);
        $this->assertSame('Suspended', $updatedList['data'][0]['status']);

        // 6. Delete Member
        $deleted = $memberModel->deleteMember($newId);
        $this->assertTrue($deleted);
    }

    public function testNotificationModelGetNotifHistory(): void
    {
        $notifModel = new NotificationModel();
        $history = $notifModel->getNotifHistory();
        $this->assertIsArray($history);
    }

    public function testFeatureContentModelGetFeaturesList(): void
    {
        $featureModel = new FeatureContentModel();
        $features = $featureModel->getFeaturesList();
        $this->assertIsArray($features);
    }

    public function testChartModelGetChartsList(): void
    {
        $chartModel = new ChartModel();
        $charts = $chartModel->getChartsList();
        $this->assertIsArray($charts);
    }

    public function testTagModelSyncArticleTags(): void
    {
        $tagModel = new TagModel();
        $dummyNanoId = 99999999;

        $tagModel->syncArticleTags($dummyNanoId, ['Unit Test Tag A', 'Unit Test Tag B']);

        $links = $this->db->table('entity_nano_teta_link')
            ->where('ref_entity_nano_id', $dummyNanoId)
            ->get()->getResultArray();

        $this->assertCount(2, $links);

        // Clean up
        $this->db->table('entity_nano_teta_link')->where('ref_entity_nano_id', $dummyNanoId)->delete();
        $tagModel->whereIn('field_label_a', ['Unit Test Tag A', 'Unit Test Tag B'])->delete();
    }

    public function testActivityLogModelLogAction(): void
    {
        $logModel = new ActivityLogModel();
        $logModel->logAction(1, 'UNIT_TEST', 'RUN', 'PHPUnit execution test');

        $inserted = $logModel->where('action_type_a', 'UNIT_TEST')
            ->where('action_type_b', 'RUN')
            ->orderBy('record_id', 'DESC')
            ->first();

        $this->assertNotNull($inserted);
        $this->assertSame('PHPUnit execution test', $inserted['log_data']);

        // Clean up
        $logModel->delete($inserted['record_id']);
    }

    public function testDataRepositoryGetAndSave(): void
    {
        DataRepository::initialize();

        $testData = ['foo' => 'bar', 'time' => time()];
        DataRepository::save('unit_test_sample', $testData);

        $fetched = DataRepository::get('unit_test_sample');
        $this->assertSame($testData['foo'], $fetched['foo']);

        $authResult = DataRepository::authenticate('raka.herdika@investor.id', 'password123');
        $this->assertIsArray($authResult);
        $this->assertSame('Raka Herdika', $authResult['name']);

        $badAuth = DataRepository::authenticate('invalid@investor.id', 'wrong');
        $this->assertNull($badAuth);
    }
}
