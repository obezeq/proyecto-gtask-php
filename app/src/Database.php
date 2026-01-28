<?php

declare(strict_types=1);

/**
 * Clase de conexion a la base de datos PostgreSQL.
 */
class Database
{
    private PDO $pdo;

    /**
     * Crea la conexion PDO usando la configuracion proporcionada.
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
            throw new PDOException('Error de conexion: ' . $e->getMessage());
        }
    }

    /**
     * Devuelve la instancia PDO.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
