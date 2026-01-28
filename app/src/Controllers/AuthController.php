<?php

declare(strict_types=1);

/**
 * Controlador de autenticacion: registro, login, logout y usuario actual.
 */
class AuthController
{
    private const MAX_NAME_LENGTH = 100;
    private const MIN_PASSWORD_LENGTH = 6;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra un nuevo usuario.
     */
    public function register(array $data): never
    {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $this->validateRegistration($name, $email, $password);
        $this->checkEmailExists($email);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password) RETURNING id'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hash,
        ]);

        $userId = (int) $stmt->fetchColumn();
        $user = ['id' => $userId, 'name' => $name, 'email' => $email];

        set_session_user($user);
        json_response(['message' => 'Registro completado.', 'user' => $user], 201);
    }

    /**
     * Inicia sesion.
     */
    public function login(array $data): never
    {
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $this->validateLogin($email, $password);

        $stmt = $this->pdo->prepare('SELECT id, name, email, password FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            json_error('Credenciales invalidas.', 401);
        }

        $userData = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        set_session_user($userData);
        json_response(['message' => 'Login correcto.', 'user' => $userData]);
    }

    /**
     * Cierra sesion.
     */
    public function logout(): never
    {
        destroy_session();
        json_response(['message' => 'Sesion cerrada.']);
    }

    /**
     * Devuelve el usuario autenticado.
     */
    public function me(): never
    {
        $user = require_auth();
        json_response(['user' => $user]);
    }

    private function validateRegistration(string $name, string $email, string $password): void
    {
        if ($name === '' || $email === '' || $password === '') {
            json_error('Nombre, email y contrasena son obligatorios.', 422);
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            json_error('El nombre no puede superar ' . self::MAX_NAME_LENGTH . ' caracteres.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('El email no es valido.', 422);
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            json_error('La contrasena debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.', 422);
        }
    }

    private function validateLogin(string $email, string $password): void
    {
        if ($email === '' || $password === '') {
            json_error('Email y contrasena son obligatorios.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('El email no es valido.', 422);
        }
    }

    private function checkEmailExists(string $email): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            json_error('El email ya esta registrado.', 409);
        }
    }
}
