<?php

declare(strict_types=1);

namespace Tests\Feature;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

// Incluir los archivos necesarios
require_once __DIR__ . '/../../src/Support.php';
require_once __DIR__ . '/../../src/Database.php';

/**
 * Clase base para tests de integracion.
 */
abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'name' => getenv('DB_NAME') ?: 'app_test',
            'user' => getenv('DB_USER') ?: 'user',
            'password' => getenv('DB_PASSWORD') ?: 'password',
        ];

        $database = new \Database($config);
        $this->pdo = $database->getConnection();

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }

    protected function createTestUser(string $email = 'test@example.com', string $name = 'Test User'): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password) RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);

        return [
            'id' => (int) $stmt->fetchColumn(),
            'name' => $name,
            'email' => $email,
        ];
    }

    protected function createTestTask(
        int $userId,
        string $title = 'Test Task',
        string $status = 'pending',
        int $priority = 0
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title, status, priority)
             VALUES (:user_id, :title, :status, :priority) RETURNING id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
        ]);

        return (int) $stmt->fetchColumn();
    }
}
