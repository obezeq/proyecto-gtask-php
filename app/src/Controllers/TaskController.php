<?php

declare(strict_types=1);

/**
 * Controlador de tareas: CRUD completo.
 */
class TaskController
{
    private const VALID_STATUSES = ['pending', 'completed'];
    private const MAX_TITLE_LENGTH = 200;
    private const MAX_DESCRIPTION_LENGTH = 1000;
    private const MIN_PRIORITY = 0;
    private const MAX_PRIORITY = 5;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las tareas del usuario.
     */
    public function index(int $userId): never
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, description, status, due_date, priority, created_at, updated_at, completed_at
             FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        json_response(['tasks' => $stmt->fetchAll()]);
    }

    /**
     * Muestra una tarea.
     */
    public function show(int $userId, int $taskId): never
    {
        $task = $this->findTask($userId, $taskId);
        json_response(['task' => $task]);
    }

    /**
     * Crea una nueva tarea.
     */
    public function create(int $userId, array $data): never
    {
        $title = trim($data['title'] ?? '');
        $description = $data['description'] ?? null;
        $status = strtolower(trim((string) ($data['status'] ?? 'pending')));
        $dueDate = $data['due_date'] ?? null;
        $priority = isset($data['priority']) ? (int) $data['priority'] : 0;

        $this->validateTaskData($title, $description, $status, $dueDate, $priority);

        $completedAt = $status === 'completed'
            ? (new DateTimeImmutable('now'))->format('Y-m-d H:i:s')
            : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (user_id, title, description, status, due_date, priority, completed_at)
             VALUES (:user_id, :title, :description, :status, :due_date, :priority, :completed_at)
             RETURNING id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'due_date' => $dueDate,
            'priority' => $priority,
            'completed_at' => $completedAt,
        ]);

        $taskId = (int) $stmt->fetchColumn();
        $this->show($userId, $taskId);
    }

    /**
     * Actualiza una tarea (parcial).
     */
    public function update(int $userId, int $taskId, array $data): never
    {
        $fields = [];
        $params = ['id' => $taskId, 'user_id' => $userId];

        $this->processUpdateFields($data, $fields, $params);

        if (empty($fields)) {
            json_error('No hay campos para actualizar.', 422);
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            json_error('Tarea no encontrada.', 404);
        }

        $this->show($userId, $taskId);
    }

    /**
     * Elimina una tarea.
     */
    public function delete(int $userId, int $taskId): never
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $taskId, 'user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            json_error('Tarea no encontrada.', 404);
        }

        json_response(['message' => 'Tarea eliminada.']);
    }

    private function findTask(int $userId, int $taskId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, description, status, due_date, priority, created_at, updated_at, completed_at
             FROM tasks WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $taskId, 'user_id' => $userId]);
        $task = $stmt->fetch();

        if (!$task) {
            json_error('Tarea no encontrada.', 404);
        }

        return $task;
    }

    private function validateTaskData(
        string $title,
        ?string $description,
        string $status,
        ?string $dueDate,
        int $priority
    ): void {
        if ($title === '') {
            json_error('El titulo es obligatorio.', 422);
        }
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            json_error('El titulo no puede superar ' . self::MAX_TITLE_LENGTH . ' caracteres.', 422);
        }
        if ($description !== null && mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            json_error('La descripcion no puede superar ' . self::MAX_DESCRIPTION_LENGTH . ' caracteres.', 422);
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            json_error('El estado no es valido.', 422);
        }
        if ($priority < self::MIN_PRIORITY || $priority > self::MAX_PRIORITY) {
            json_error('La prioridad debe estar entre ' . self::MIN_PRIORITY . ' y ' . self::MAX_PRIORITY . '.', 422);
        }
        if ($dueDate !== null && !$this->isValidDate($dueDate)) {
            json_error('La fecha limite debe tener formato YYYY-MM-DD.', 422);
        }
    }

    private function processUpdateFields(array $data, array &$fields, array &$params): void
    {
        if (array_key_exists('title', $data)) {
            $title = trim((string) $data['title']);
            if ($title === '') {
                json_error('El titulo no puede estar vacio.', 422);
            }
            if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
                json_error('El titulo no puede superar ' . self::MAX_TITLE_LENGTH . ' caracteres.', 422);
            }
            $fields[] = 'title = :title';
            $params['title'] = $title;
        }

        if (array_key_exists('description', $data)) {
            if ($data['description'] !== null && mb_strlen((string) $data['description']) > self::MAX_DESCRIPTION_LENGTH) {
                json_error('La descripcion no puede superar ' . self::MAX_DESCRIPTION_LENGTH . ' caracteres.', 422);
            }
            $fields[] = 'description = :description';
            $params['description'] = $data['description'];
        }

        if (array_key_exists('status', $data)) {
            $status = strtolower(trim((string) $data['status']));
            if (!in_array($status, self::VALID_STATUSES, true)) {
                json_error('El estado no es valido.', 422);
            }
            $fields[] = 'status = :status';
            $params['status'] = $status;
            if ($status === 'completed') {
                $fields[] = 'completed_at = :completed_at';
                $params['completed_at'] = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            } else {
                $fields[] = 'completed_at = NULL';
            }
        }

        if (array_key_exists('due_date', $data)) {
            if ($data['due_date'] !== null && !$this->isValidDate($data['due_date'])) {
                json_error('La fecha limite debe tener formato YYYY-MM-DD.', 422);
            }
            $fields[] = 'due_date = :due_date';
            $params['due_date'] = $data['due_date'];
        }

        if (array_key_exists('priority', $data)) {
            $priority = (int) $data['priority'];
            if ($priority < self::MIN_PRIORITY || $priority > self::MAX_PRIORITY) {
                json_error('La prioridad debe estar entre ' . self::MIN_PRIORITY . ' y ' . self::MAX_PRIORITY . '.', 422);
            }
            $fields[] = 'priority = :priority';
            $params['priority'] = $priority;
        }
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
