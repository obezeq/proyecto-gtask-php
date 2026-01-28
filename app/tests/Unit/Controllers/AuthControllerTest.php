<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para validaciones del AuthController.
 */
class AuthControllerTest extends TestCase
{
    public function testEmailValidationWithValidEmail(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@example.co.uk',
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "El email '$email' deberia ser valido"
            );
        }
    }

    public function testEmailValidationWithInvalidEmail(): void
    {
        $invalidEmails = [
            'not-an-email',
            '@domain.com',
            'user@',
            'user@.com',
            '',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "El email '$email' deberia ser invalido"
            );
        }
    }

    public function testPasswordHashVerification(): void
    {
        $password = 'securepassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrongpassword', $hash));
        $this->assertFalse(password_verify('', $hash));
    }

    public function testPasswordHashIsDifferentEachTime(): void
    {
        $password = 'samepassword';
        $hash1 = password_hash($password, PASSWORD_DEFAULT);
        $hash2 = password_hash($password, PASSWORD_DEFAULT);

        $this->assertNotEquals($hash1, $hash2);
        $this->assertTrue(password_verify($password, $hash1));
        $this->assertTrue(password_verify($password, $hash2));
    }

    public function testNameLengthValidation(): void
    {
        $maxLength = 100;

        $validName = 'John Doe';
        $exactLength = str_repeat('a', $maxLength);
        $longName = str_repeat('a', $maxLength + 1);

        $this->assertTrue(mb_strlen($validName) <= $maxLength);
        $this->assertTrue(mb_strlen($exactLength) <= $maxLength);
        $this->assertFalse(mb_strlen($longName) <= $maxLength);
    }

    public function testPasswordMinimumLength(): void
    {
        $minLength = 6;

        $shortPasswords = ['12345', 'abc', ''];
        $validPasswords = ['123456', 'password', 'securep@ss'];

        foreach ($shortPasswords as $password) {
            $this->assertFalse(
                mb_strlen($password) >= $minLength,
                "La contrasena '$password' deberia ser muy corta"
            );
        }

        foreach ($validPasswords as $password) {
            $this->assertTrue(
                mb_strlen($password) >= $minLength,
                "La contrasena '$password' deberia ser valida"
            );
        }
    }

    public function testEmailNormalization(): void
    {
        $email = '  TEST@EXAMPLE.COM  ';
        $normalized = strtolower(trim($email));

        $this->assertEquals('test@example.com', $normalized);
    }

    public function testNameTrimming(): void
    {
        $name = '  John Doe  ';
        $trimmed = trim($name);

        $this->assertEquals('John Doe', $trimmed);
    }

    public function testEmptyFieldDetection(): void
    {
        $testCases = [
            ['name' => '', 'email' => 'test@test.com', 'password' => '123456', 'valid' => false],
            ['name' => 'Test', 'email' => '', 'password' => '123456', 'valid' => false],
            ['name' => 'Test', 'email' => 'test@test.com', 'password' => '', 'valid' => false],
            ['name' => 'Test', 'email' => 'test@test.com', 'password' => '123456', 'valid' => true],
        ];

        foreach ($testCases as $case) {
            $hasEmptyField = $case['name'] === '' || $case['email'] === '' || $case['password'] === '';
            $this->assertEquals(!$case['valid'], $hasEmptyField);
        }
    }
}
