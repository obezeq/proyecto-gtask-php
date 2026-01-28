<?php

declare(strict_types=1);

namespace Tests\Feature;

/**
 * Tests de integracion para la API de tareas.
 */
class TaskApiTest extends TestCase
{
    public function testTaskCanBeCreated(): void
    {
        $user = $this->createTestUser();

        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title, description, status, priority)
             VALUES (:user_id, :title, :description, :status, :priority) RETURNING id'
        );
        $stmt->execute([
            'user_id' => $user['id'],
            'title' => 'Nueva tarea',
            'description' => 'Descripcion de prueba',
            'status' => 'pending',
            'priority' => 3,
        ]);

        $taskId = (int) $stmt->fetchColumn();
        $this->assertGreaterThan(0, $taskId);
    }

    public function testTasksBelongToUser(): void
    {
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');

        $task1 = $this->createTestTask($user1['id'], 'Tarea de User1');
        $task2 = $this->createTestTask($user2['id'], 'Tarea de User2');

        $stmt = $this->pdo->prepare('SELECT id FROM tasks WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user1['id']]);
        $user1Tasks = $stmt->fetchAll();

        $this->assertCount(1, $user1Tasks);
        $this->assertEquals($task1, $user1Tasks[0]['id']);
    }

    public function testTaskCanBeUpdated(): void
    {
        $user = $this->createTestUser();
        $taskId = $this->createTestTask($user['id']);

        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET title = :title, status = :status, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'title' => 'Titulo actualizado',
            'status' => 'completed',
            'id' => $taskId,
            'user_id' => $user['id'],
        ]);

        $stmt = $this->pdo->prepare('SELECT title, status FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);
        $updated = $stmt->fetch();

        $this->assertEquals('Titulo actualizado', $updated['title']);
        $this->assertEquals('completed', $updated['status']);
    }

    public function testTaskCanBeDeleted(): void
    {
        $user = $this->createTestUser();
        $taskId = $this->createTestTask($user['id']);

        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $taskId, 'user_id' => $user['id']]);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $taskId]);

        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testTaskListOrderedByCreatedAt(): void
    {
        $user = $this->createTestUser();

        $task1 = $this->createTestTask($user['id'], 'Primera');
        $task2 = $this->createTestTask($user['id'], 'Segunda');
        $task3 = $this->createTestTask($user['id'], 'Tercera');

        $stmt = $this->pdo->prepare(
            'SELECT id FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $user['id']]);
        $tasks = $stmt->fetchAll();

        $this->assertCount(3, $tasks);
        $this->assertEquals($task3, $tasks[0]['id']);
        $this->assertEquals($task2, $tasks[1]['id']);
        $this->assertEquals($task1, $tasks[2]['id']);
    }

    public function testCannotAccessOtherUserTasks(): void
    {
        $user1 = $this->createTestUser('owner@example.com');
        $user2 = $this->createTestUser('attacker@example.com');

        $taskId = $this->createTestTask($user1['id'], 'Tarea privada');

        $stmt = $this->pdo->prepare(
            'SELECT * FROM tasks WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $taskId, 'user_id' => $user2['id']]);
        $task = $stmt->fetch();

        $this->assertFalse($task);
    }

    public function testTaskHasDefaultStatus(): void
    {
        $user = $this->createTestUser();

        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title) VALUES (:user_id, :title) RETURNING status'
        );
        $stmt->execute([
            'user_id' => $user['id'],
            'title' => 'Task with default status',
        ]);

        $status = $stmt->fetchColumn();
        $this->assertEquals('pending', $status);
    }

    public function testTaskHasDefaultPriority(): void
    {
        $user = $this->createTestUser();

        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title) VALUES (:user_id, :title) RETURNING priority'
        );
        $stmt->execute([
            'user_id' => $user['id'],
            'title' => 'Task with default priority',
        ]);

        $priority = $stmt->fetchColumn();
        $this->assertEquals(0, $priority);
    }

    public function testTaskHasTimestamps(): void
    {
        $user = $this->createTestUser();

        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title) VALUES (:user_id, :title)
             RETURNING created_at, updated_at'
        );
        $stmt->execute([
            'user_id' => $user['id'],
            'title' => 'Task with timestamps',
        ]);

        $result = $stmt->fetch();
        $this->assertNotNull($result['created_at']);
        $this->assertNotNull($result['updated_at']);
    }

    public function testTaskPriorityRange(): void
    {
        $user = $this->createTestUser();

        // Crear tareas con diferentes prioridades validas
        for ($priority = 0; $priority <= 5; $priority++) {
            $taskId = $this->createTestTask($user['id'], "Tarea prioridad $priority", 'pending', $priority);
            $this->assertGreaterThan(0, $taskId);
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user['id']]);

        $this->assertEquals(6, $stmt->fetchColumn());
    }

    public function testDeleteOnlyAffectsUserOwnTask(): void
    {
        $user1 = $this->createTestUser('user1@test.com');
        $user2 = $this->createTestUser('user2@test.com');

        $task1 = $this->createTestTask($user1['id'], 'Tarea 1');
        $task2 = $this->createTestTask($user2['id'], 'Tarea 2');

        // Intentar eliminar tarea de otro usuario
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $task2, 'user_id' => $user1['id']]);

        // La tarea del user2 deberia seguir existiendo
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $task2]);

        $this->assertEquals(1, $stmt->fetchColumn());
    }
}
