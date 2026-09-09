<?php

namespace Tests\Unit\Models;

use App\Models\AdminModel;
use CodeIgniter\Test\CIUnitTestCase;

final class AdminModelTest extends CIUnitTestCase
{
    protected AdminModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AdminModel();
    }

    public function testGenerateSaltLength(): void
    {
        $salt = AdminModel::generateSalt(16);
        $this->assertSame(16, strlen($salt));

        $salt32 = AdminModel::generateSalt(32);
        $this->assertSame(32, strlen($salt32));
    }

    public function testEncodeAndDecodePasswordReversible(): void
    {
        $password = 'SecretPass123!@#';
        $salt = AdminModel::generateSalt(16);

        $encoded = AdminModel::encodePassword($password, $salt);
        $this->assertNotEmpty($encoded);
        $this->assertNotSame($password, $encoded);

        $decoded = AdminModel::decodePassword($encoded, $salt);
        $this->assertSame($password, $decoded);
    }

    public function testDecodePasswordWithInvalidHashOrSaltReturnsNull(): void
    {
        $this->assertNull(AdminModel::decodePassword('', 'some_salt'));
        $this->assertNull(AdminModel::decodePassword('invalid_hash', ''));
    }

    public function testAuthenticateWithValidCredentials(): void
    {
        $user = $this->model->authenticate('raka_h', 'password123');
        $this->assertNotNull($user);
        $this->assertSame('raka_h', $user['attr_identifier']);
    }

    public function testAuthenticateWithInvalidPasswordReturnsNull(): void
    {
        $user = $this->model->authenticate('raka_h', 'wrong_password');
        $this->assertNull($user);
    }

    public function testAuthenticateWithNonExistentUserReturnsNull(): void
    {
        $user = $this->model->authenticate('non_existent_user_xyz', 'some_pass');
        $this->assertNull($user);
    }

    public function testGetAdminsWithActivityReturnsArray(): void
    {
        $admins = $this->model->getAdminsWithActivity();
        $this->assertIsArray($admins);
        $this->assertNotEmpty($admins);
        $this->assertArrayHasKey('status', $admins[0]);
        $this->assertArrayHasKey('last_active', $admins[0]);
    }
}
