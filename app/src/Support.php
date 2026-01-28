<?php

declare(strict_types=1);

/**
 * Funciones de soporte para la API.
 * Incluye manejo de JSON, autenticacion y CORS.
 */

// ============================================================
// RESPUESTAS JSON
// ============================================================

/**
 * Envia una respuesta JSON y termina la ejecucion.
 */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envia un error JSON y termina la ejecucion.
 */
function json_error(string $message, int $status = 400): never
{
    json_response(['error' => $message], $status);
}

// ============================================================
// LECTURA DEL BODY
// ============================================================

/**
 * Lee el cuerpo JSON de la peticion.
 */
function get_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('JSON invalido.', 400);
    }
    return $data;
}

// ============================================================
// AUTENTICACION (sesiones)
// ============================================================

/**
 * Verifica que el usuario este autenticado.
 * Si no lo esta, devuelve error 401.
 */
function require_auth(): array
{
    if (empty($_SESSION['user'])) {
        json_error('No autenticado.', 401);
    }
    return $_SESSION['user'];
}

/**
 * Guarda el usuario en la sesion.
 */
function set_session_user(array $user): void
{
    $_SESSION['user'] = $user;
}

/**
 * Obtiene el usuario de la sesion (o null si no hay).
 */
function get_session_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Cierra la sesion.
 */
function destroy_session(): void
{
    $_SESSION = [];
    session_destroy();
}

// ============================================================
// CORS
// ============================================================

/**
 * Aplica cabeceras CORS y responde a preflight OPTIONS.
 */
function apply_cors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
