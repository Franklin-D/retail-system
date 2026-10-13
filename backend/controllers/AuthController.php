<?php
/**
 * controllers/AuthController.php
 */

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/auth.middleware.php';

class AuthController
{
    private AuthService $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($input['email']) || empty($input['password'])) {
            Response::error('El email y la contraseña son requeridos', 400);
        }

        try {
            $result = $this->authService->login($input['email'], $input['password']);
            Response::success($result, 'Inicio de sesión exitoso');
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function me(): void {
        $userPayload = requireAuth();
        try {
            $profile = $this->authService->getProfile($userPayload['sub']);
            Response::success($profile, 'Perfil obtenido correctamente');
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}