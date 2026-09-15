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
        // Verify eye toggle and security guidelines
        $this->assertStringContainsString('togglePasswordVisibility', $body);
        $this->assertStringContainsString('data-lucide="eye"', $body);
        $this->assertStringContainsString('huruf besar, huruf kecil, angka, dan karakter simbol', $body);
    }

    public function testPasswordStrengthValidation()
    {
        // Lemah: Kurang dari 8 karakter
        $err1 = \App\Models\AdminModel::validatePasswordStrength('Ab1!');
        $this->assertNotEmpty($err1);

        // Lemah: Tanpa simbol
        $err2 = \App\Models\AdminModel::validatePasswordStrength('Password123');
        $this->assertNotEmpty($err2);

        // Lemah: Tanpa huruf besar
        $err3 = \App\Models\AdminModel::validatePasswordStrength('password123!');
        $this->assertNotEmpty($err3);

        // Lemah: Tanpa angka
        $err4 = \App\Models\AdminModel::validatePasswordStrength('Password!@#');
        $this->assertNotEmpty($err4);

        // Kuat: Memenuhi seluruh syarat
        $errStrong = \App\Models\AdminModel::validatePasswordStrength('Investor@2026!');
        $this->assertEmpty($errStrong);
    }
}
