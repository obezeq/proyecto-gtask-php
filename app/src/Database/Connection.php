<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Clase de conexion a PostgreSQL usando PDO.
 */
final class Connection
{
    private PDO $pdo;

    /**
     * @param array<string, string> $config Configuracion de conexion (host, port, name, user, password)
     * @throws PDOException Si falla la conexion
     */
    public function __construct(array $config)
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['name']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new PDOException('Error de conexion a la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Devuelve la instancia PDO para ejecutar consultas.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
