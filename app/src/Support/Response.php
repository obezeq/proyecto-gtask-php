<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Clase para manejar respuestas JSON de la API.
 */
final class Response
{
    /**
     * Envia una respuesta JSON y termina la ejecucion.
     *
     * @param array<string, mixed> $payload Datos a enviar
     * @param int $status Codigo HTTP
     */
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Envia una respuesta de error JSON.
     *
     * @param string $message Mensaje de error
     * @param int $status Codigo HTTP de error
     * @param array<string, mixed> $extra Datos adicionales
     */
    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['error' => $message], $extra), $status);
    }
}
