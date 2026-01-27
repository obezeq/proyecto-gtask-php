<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Clase para manejar datos de la peticion HTTP.
 */
final class Request
{
    /**
     * Lee y parsea el cuerpo JSON de la peticion.
     *
     * @return array<string, mixed> Datos del body
     */
    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            Response::error('JSON invalido.', 400);
        }

        return $data;
    }

    /**
     * Obtiene el metodo HTTP de la peticion.
     */
    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Obtiene la ruta de la peticion (sin query string).
     */
    public static function getPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
