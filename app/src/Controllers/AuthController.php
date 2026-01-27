<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Response;
use PDO;

/**
 * Controlador de autenticacion: registro, login, logout y datos del usuario actual.
 */
final class AuthController
{
    private const MAX_NAME_LENGTH = 100;
    private const MIN_PASSWORD_LENGTH = 6;

    public function __construct(
        private readonly PDO $pdo
    ) {}

    /**
     * Registra un nuevo usuario.
     *
     * @param array<string, mixed> $data Datos del formulario (name, email, password)
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

        Auth::setUser($user);
        Response::json(['message' => 'Registro completado.', 'user' => $user], 201);
    }

    /**
     * Inicia sesion de un usuario existente.
     *
     * @param array<string, mixed> $data Datos del formulario (email, password)
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
            Response::error('Credenciales invalidas.', 401);
        }

        $userData = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        Auth::setUser($userData);
        Response::json(['message' => 'Login correcto.', 'user' => $userData]);
    }

    /**
     * Cierra la sesion del usuario actual.
     */
    public function logout(): never
    {
        Auth::logout();
        Response::json(['message' => 'Sesion cerrada.']);
    }

    /**
     * Devuelve los datos del usuario autenticado.
     */
    public function me(): never
    {
        $user = Auth::requireAuth();
        Response::json(['user' => $user]);
    }

    /**
     * Valida los datos de registro.
     */
    private function validateRegistration(string $name, string $email, string $password): void
    {
        if ($name === '' || $email === '' || $password === '') {
            Response::error('Nombre, email y contrasena son obligatorios.', 422);
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            Response::error('El nombre no puede superar ' . self::MAX_NAME_LENGTH . ' caracteres.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El email no es valido.', 422);
        }
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            Response::error('La contrasena debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.', 422);
        }
    }

    /**
     * Valida los datos de login.
     */
    private function validateLogin(string $email, string $password): void
    {
        if ($email === '' || $password === '') {
            Response::error('Email y contrasena son obligatorios.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El email no es valido.', 422);
        }
    }

    /**
     * Verifica si el email ya esta registrado.
     */
    private function checkEmailExists(string $email): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            Response::error('El email ya esta registrado.', 409);
        }
    }
}
