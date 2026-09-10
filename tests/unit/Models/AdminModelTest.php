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
        $defaultSalt = AdminModel::generateSalt();
        $this->assertSame(128, strlen($defaultSalt));
        $this->assertTrue(ctype_xdigit($defaultSalt));

        $salt64 = AdminModel::generateSalt(64);
        $this->assertSame(64, strlen($salt64));
    }

    public function testEncodePasswordProduces128HexChars(): void
    {
        $password = 'SecretPass123!@#';
        $salt = AdminModel::generateSalt(128);

        $encoded = AdminModel::encodePassword($password, $salt);
        $this->assertNotEmpty($encoded);
        $this->assertSame(128, strlen($encoded));
        $this->assertTrue(ctype_xdigit($encoded));
        $this->assertSame(hash('sha512', $salt . $password), $encoded);
    }

    public function testVerifyPassword(): void
    {
        $password = 'SecretPass123!@#';
        $salt = AdminModel::generateSalt(128);
        $hash = AdminModel::encodePassword($password, $salt);

        $this->assertTrue(AdminModel::verifyPassword($password, $salt, $hash));
        $this->assertFalse(AdminModel::verifyPassword('wrong_pass', $salt, $hash));
        $this->assertFalse(AdminModel::verifyPassword($password, 'wrong_salt', $hash));
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
        $this->assertSame('raka_h', $user['username']);
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
