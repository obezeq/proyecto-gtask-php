<?php

declare(strict_types=1);

/**
 * Punto de entrada de la aplicacion.
 * Maneja el routing y delega a los controladores.
 */

// Includes
require_once __DIR__ . '/../src/Support.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/TaskController.php';

// Sesion
session_start();

// CORS
apply_cors();

// Configuracion y conexion a BD
$config = require __DIR__ . '/../config/config.php';
$database = new Database($config['db']);
$pdo = $database->getConnection();

// Controllers
$authController = new AuthController($pdo);
$taskController = new TaskController($pdo);

// Metodo y ruta
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// Pagina principal (cliente web)
if ($path === '/' && $method === 'GET') {
    require __DIR__ . '/views/app.php';
    exit;
}

// Parsear ruta: /api/recurso/id
$segments = explode('/', trim($path, '/'));

if ($segments[0] !== 'api') {
    json_error('Ruta no encontrada.', 404);
}

$resource = $segments[1] ?? '';
$id = $segments[2] ?? null;

// Rutas de autenticacion (publicas)
match (true) {
    $resource === 'register' && $method === 'POST' => $authController->register(get_json_body()),
    $resource === 'login' && $method === 'POST' => $authController->login(get_json_body()),
    $resource === 'logout' && $method === 'POST' => $authController->logout(),
    $resource === 'me' && $method === 'GET' => $authController->me(),
    default => null,
};

// Rutas de tareas (protegidas)
if ($resource === 'tasks') {
    $user = require_auth();
    $userId = (int) $user['id'];

    match (true) {
        $method === 'GET' && $id === null => $taskController->index($userId),
        $method === 'POST' && $id === null => $taskController->create($userId, get_json_body()),
        $id !== null && $method === 'GET' => $taskController->show($userId, (int) $id),
        $id !== null && in_array($method, ['PUT', 'PATCH'], true) => $taskController->update($userId, (int) $id, get_json_body()),
        $id !== null && $method === 'DELETE' => $taskController->delete($userId, (int) $id),
        default => null,
    };
}

// Si no coincide ninguna ruta
json_error('Ruta no encontrada.', 404);
