<?php

namespace Retail\Controllers;

use Throwable;
use Retail\Middleware\AuthMiddleware;
use Retail\Services\AuthService;
use Retail\Utils\Request;
use Retail\Utils\Response;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(): void
    {
        $input = Request::input();
        if (empty($input['email']) || empty($input['password'])) {
            Response::error('El email y la contraseña son requeridos', 400);
        }
        try {
            $perfil = $this->authService->login($input['email'], $input['password']);
            Response::success($perfil, 'Inicio de sesión exitoso');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        Response::success(null, 'Sesión cerrada');
    }

    public function me(): void
    {
        $sesion = AuthMiddleware::requireAuth();
        try {
            $perfil = $this->authService->getProfile((int) $sesion['id']);
            Response::success($perfil, 'Perfil obtenido correctamente');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }
}
