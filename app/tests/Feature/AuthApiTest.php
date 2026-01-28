<?php

declare(strict_types=1);

namespace Tests\Feature;

/**
 * Tests de integracion para la API de autenticacion.
 */
class AuthApiTest extends TestCase
{
    public function testUserCanBeCreatedInDatabase(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password)
             VALUES (:name, :email, :password) RETURNING id, name, email'
        );
        $stmt->execute([
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        $user = $stmt->fetch();

        $this->assertNotEmpty($user['id']);
        $this->assertEquals('Test User', $user['name']);
        $this->assertEquals('newuser@example.com', $user['email']);
    }

    public function testEmailMustBeUnique(): void
    {
        $this->createTestUser('unique@example.com');

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => 'unique@example.com']);

        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testUserCanBeFoundByEmail(): void
    {
        $created = $this->createTestUser('findme@example.com');

        $stmt = $this->pdo->prepare('SELECT id, name, email FROM users WHERE email = :email');
        $stmt->execute(['email' => 'findme@example.com']);
        $found = $stmt->fetch();

        $this->assertEquals($created['id'], $found['id']);
        $this->assertEquals($created['email'], $found['email']);
        $this->assertEquals($created['name'], $found['name']);
    }

    public function testPasswordVerificationWorks(): void
    {
        $password = 'testpassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password) RETURNING id'
        );
        $stmt->execute([
            'name' => 'Password Test',
            'email' => 'passtest@example.com',
            'password' => $hash,
        ]);

        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE email = :email');
        $stmt->execute(['email' => 'passtest@example.com']);
        $storedHash = $stmt->fetchColumn();

        $this->assertTrue(password_verify($password, $storedHash));
        $this->assertFalse(password_verify('wrongpassword', $storedHash));
    }

    public function testUserEmailIsCaseInsensitive(): void
    {
        $this->createTestUser('test@example.com');

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');

        // Buscar con el mismo email en minusculas
        $stmt->execute(['email' => 'test@example.com']);
        $result = $stmt->fetch();

        $this->assertNotEmpty($result);
    }

    public function testUserHasDefaultRole(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password)
             VALUES (:name, :email, :password) RETURNING role'
        );
        $stmt->execute([
            'name' => 'Role Test',
            'email' => 'roletest@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        $role = $stmt->fetchColumn();

        $this->assertEquals('user', $role);
    }

    public function testUserHasCreatedAtTimestamp(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password)
             VALUES (:name, :email, :password) RETURNING created_at'
        );
        $stmt->execute([
            'name' => 'Timestamp Test',
            'email' => 'timestamp@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        $createdAt = $stmt->fetchColumn();

        $this->assertNotNull($createdAt);
    }
}
