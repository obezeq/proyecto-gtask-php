<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Clase para manejar autenticacion basada en sesiones.
 */
final class Auth
{
    /**
     * Verifica que el usuario este autenticado.
     * Termina con error 401 si no hay sesion activa.
     *
     * @return array<string, mixed> Datos del usuario autenticado
     */
    public static function requireAuth(): array
    {
        if (empty($_SESSION['user'])) {
            Response::error('No autenticado.', 401);
        }

        return $_SESSION['user'];
    }

    /**
     * Guarda los datos del usuario en la sesion.
     *
     * @param array<string, mixed> $user Datos del usuario
     */
    public static function setUser(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    /**
     * Obtiene el usuario actual de la sesion (si existe).
     *
     * @return array<string, mixed>|null
     */
    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Cierra la sesion del usuario.
     */
    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
