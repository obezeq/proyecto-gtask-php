<?php

declare(strict_types=1);

// Autoload de Composer (PSR-4)
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Database\Connection;
use App\Support\Auth;
use App\Support\Cors;
use App\Support\Request;
use App\Support\Response;

// Iniciamos la sesion para mantener al usuario autenticado.
session_start();

// CORS: cabeceras y respuesta a preflight OPTIONS.
Cors::apply();

// Configuracion (variables de entorno) y conexion a BD.
$config = require __DIR__ . '/../config/config.php';
$database = new Connection($config['db']);
$authController = new AuthController($database->pdo());
$taskController = new TaskController($database->pdo());

// Metodo HTTP y ruta pedida por el cliente.
$method = Request::getMethod();
$path = Request::getPath();

// Pagina principal con el cliente web (solo GET /).
if ($path === '/' && $method === 'GET') {
    require __DIR__ . '/views/app.php';
    exit;
}

// Dividimos la ruta en partes: /api/recurso/id
$segments = explode('/', trim($path, '/'));

if ($segments[0] !== 'api') {
    Response::error('Ruta no encontrada.', 404);
}

// Recurso y posible id.
$resource = $segments[1] ?? '';
$id = $segments[2] ?? null;

// Rutas de autenticacion (no requieren estar logueado).
match (true) {
    $resource === 'register' && $method === 'POST' => $authController->register(Request::getJsonBody()),
    $resource === 'login' && $method === 'POST' => $authController->login(Request::getJsonBody()),
    $resource === 'logout' && $method === 'POST' => $authController->logout(),
    $resource === 'me' && $method === 'GET' => $authController->me(),
    default => null,
};

// Rutas de tareas (requieren autenticacion).
if ($resource === 'tasks') {
    $user = Auth::requireAuth();
    $userId = (int) $user['id'];

    match (true) {
        $method === 'GET' && $id === null => $taskController->index($userId),
        $method === 'POST' && $id === null => $taskController->create($userId, Request::getJsonBody()),
        $id !== null && $method === 'GET' => $taskController->show($userId, (int) $id),
        $id !== null && in_array($method, ['PUT', 'PATCH'], true) => $taskController->update($userId, (int) $id, Request::getJsonBody()),
        $id !== null && $method === 'DELETE' => $taskController->delete($userId, (int) $id),
        default => null,
    };
}

// Si no coincide ninguna ruta, error 404.
Response::error('Ruta no encontrada.', 404);
