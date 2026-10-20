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

        $deterministicSalt = AdminModel::generateSalt('raka_h', 'password123');
        $this->assertSame(128, strlen($deterministicSalt));
        $this->assertSame(hash('sha512', '[raka_h>|<password123]'), $deterministicSalt);
    }

    public function testEncodePasswordProduces128HexChars(): void
    {
        $username = 'raka_h';
        $password = 'SecretPass123!@#';
        $salt = AdminModel::generateSalt($username, $password);

        $encoded = AdminModel::encodePassword($password, $salt);
        $this->assertNotEmpty($encoded);
        $this->assertSame(128, strlen($encoded));
        $this->assertTrue(ctype_xdigit($encoded));
        $this->assertSame(hash('sha512', $salt . AdminModel::LEGACY_PEPPER . $password . $salt), $encoded);
    }

    public function testVerifyPassword(): void
    {
        $username = 'raka_h';
        $password = 'SecretPass123!@#';
        $salt = AdminModel::generateSalt($username, $password);
        $hash = AdminModel::encodePassword($password, $salt);

        // Test verify with username
        $this->assertTrue(AdminModel::verifyPassword($password, $salt, $hash, $username));
        // Test verify without username (stored salt)
        $this->assertTrue(AdminModel::verifyPassword($password, $salt, $hash));
        // Test invalid password / salt
        $this->assertFalse(AdminModel::verifyPassword('wrong_pass', $salt, $hash, $username));
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
        $testUsername = 'test_smarty_user_' . time();
        $plainPass = 'smartySecret123';
        $md5Hash = md5($plainPass);

        // Insert user with MD5 hash
        $userId = $this->model->insert([
            'username' => $testUsername,
            'name'     => 'Smarty User',
            'alias'    => 'SMR',
            'slug'     => 'smarty-user',
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

    public function testExactSmartyParity(): void
    {
        // Sample usernames with mixed case to verify lowercase normalization
        $cases = [
            'Radyan'     => 'MySecretPass123!',
            'SUPPORT'    => 'Investor#2026',
            'jauhari_id' => 'Pass@word123',
        ];

        foreach ($cases as $username => $password) {
            // Smarty Administrator.class.php formula
            $smartySalt = hash('SHA512', '[' . strtolower($username) . '>|<' . $password . ']', false);
            $smartyPass = hash('SHA512', $smartySalt . 'B3!21t454t03' . $password . $smartySalt, false);

            // CI4 AdminModel
            $ci4Salt = AdminModel::generateSalt($username, $password);
            $ci4Pass = AdminModel::encodePassword($password, $ci4Salt);

            $this->assertSame($smartySalt, $ci4Salt, "Salt mismatch for $username");
            $this->assertSame($smartyPass, $ci4Pass, "Password hash mismatch for $username");
            $this->assertTrue(AdminModel::verifyPassword($password, $ci4Salt, $smartyPass, $username));
        }
    }
}
