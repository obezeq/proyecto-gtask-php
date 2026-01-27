<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Clase para manejar cabeceras CORS.
 */
final class Cors
{
    /**
     * Aplica las cabeceras CORS segun configuracion de entorno.
     * Responde automaticamente a peticiones OPTIONS (preflight).
     */
    public static function apply(): void
    {
        $enabled = getenv('CORS_ENABLED');
        if ($enabled !== false && strtolower(trim($enabled)) === 'false') {
            return;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = getenv('ALLOWED_ORIGINS') ?: '';
        $allowedMethods = getenv('ALLOWED_METHODS') ?: 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
        $allowedHeaders = getenv('ALLOWED_HEADERS') ?: 'Content-Type';
        $allowCredentials = getenv('ALLOW_CREDENTIALS') ?: 'true';

        $origins = array_filter(array_map('trim', explode(',', $allowedOrigins)));
        $allowAll = in_array('*', $origins, true);
        $originAllowed = $origin !== '' && ($allowAll || in_array($origin, $origins, true));

        if ($originAllowed) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        } elseif ($allowAll && strtolower($allowCredentials) !== 'true') {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: ' . $allowedMethods);
        header('Access-Control-Allow-Headers: ' . $allowedHeaders);

        if (strtolower($allowCredentials) === 'true') {
            header('Access-Control-Allow-Credentials: true');
        }

        // Preflight: responde a OPTIONS y termina
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
