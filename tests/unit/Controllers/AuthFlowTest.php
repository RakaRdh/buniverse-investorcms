<?php

namespace Tests\Unit\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Auth;

class AuthFlowTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    public function testForgotPasswordViewRenders()
    {
        unset($_SESSION['user_id']);
        $result = $this->withURI('http://localhost:8080/auth/forgot-password')
            ->controller(Auth::class)
            ->execute('forgotPassword');

        $this->assertTrue($result->isOK());
        $body = $result->getBody();
        $this->assertStringContainsString('Lupa kata sandi?', $body);
        $this->assertStringContainsString('Kirim Tautan Pemulihan', $body);
    }

    public function testResetPasswordViewRenders()
    {
        unset($_SESSION['user_id']);
        $result = $this->withURI('http://localhost:8080/auth/reset-password?token=abcdef1234567890&user=raka_h')
            ->controller(Auth::class)
            ->execute('resetPassword');

        $this->assertTrue($result->isOK());
        $body = $result->getBody();
        $this->assertStringContainsString('Setel Kata Sandi Baru', $body);
        $this->assertStringContainsString('Kata Sandi Baru', $body);
    }
}
