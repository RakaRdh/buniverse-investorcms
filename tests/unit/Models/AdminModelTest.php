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

    public function testAutoUpgradeFromMd5ToSha512(): void
    {
        $testUsername = 'test_legacy_user_' . time();
        $plainPass = 'legacySecret123';
        $md5Hash = md5($plainPass);

        // Insert legacy user with MD5 hash
        $userId = $this->model->insert([
            'username' => $testUsername,
            'name'     => 'Legacy User',
            'alias'    => 'LGU',
            'slug'     => 'legacy-user',
            'email'    => $testUsername . '@investor.id',
            'password' => $md5Hash,
            'salt'     => '',
            'active'   => 1,
            'roleName' => 'editor'
        ]);
        $this->assertGreaterThan(0, $userId);

        // Authenticate should succeed via Strategy 4 and auto-upgrade to SHA-512
        $auth = $this->model->authenticate($testUsername, $plainPass);
        $this->assertNotNull($auth);
        $this->assertSame($testUsername, $auth['username']);

        // Check updated record has 128-char salt and SHA-512 hash
        $updated = $this->model->find($userId);
        $this->assertSame(128, strlen($updated['salt']));
        $this->assertSame(128, strlen($updated['password']));
        $this->assertNotSame($md5Hash, $updated['password']);

        // Cleanup
        $this->model->delete($userId);
    }
}
