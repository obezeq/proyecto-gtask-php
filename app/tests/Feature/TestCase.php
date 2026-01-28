<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Clase base para tests de integracion con base de datos.
 * Usa transacciones para mantener la BD limpia entre tests.
 */
abstract class TestCase extends BaseTestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'host' => getenv('DB_HOST') ?: 'db',
            'port' => getenv('DB_PORT') ?: '5432',
            'name' => getenv('DB_NAME') ?: 'app_test',
            'user' => getenv('DB_USER') ?: 'user',
            'password' => getenv('DB_PASSWORD') ?: 'password',
        ];

        $connection = new Connection($config);
        $this->pdo = $connection->pdo();

        $this->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollbackTransaction();
        parent::tearDown();
    }

    /**
     * Inicia una transaccion para aislar los cambios del test.
     */
    protected function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Revierte la transaccion para limpiar los cambios.
     */
    protected function rollbackTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Crea un usuario de prueba en la BD.
     *
     * @return array<string, mixed> Datos del usuario creado
     */
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

    /**
     * Crea una tarea de prueba en la BD.
     *
     * @return int ID de la tarea creada
     */
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
