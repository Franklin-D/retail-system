<?php
// backend/services/AuthService.php

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthService
{
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function login(string $email, string $password): array {
        $usuario = $this->userRepo->findByEmail($email);

        if (!$usuario || !password_verify($password, $usuario->password)) {
            throw new Exception("Credenciales incorrectas", 401);
        }

        if ($usuario->estado === 0) {
            throw new Exception("El usuario se encuentra inactivo", 403);
        }

        $payload = [
            'sub'   => $usuario->id,
            'email' => $usuario->email,
            'rol'   => $usuario->rol
        ];

        $token = JWT::generate($payload);

        return [
            'token' => $token,
            'user'  => $usuario->toArrayWithoutPassword()
        ];
    }

    public function getProfile(int $id): array {
        $usuario = $this->userRepo->findById($id);
        if (!$usuario) {
            throw new Exception("Usuario no encontrado", 404);
        }
        return $usuario->toArrayWithoutPassword();
    }
}