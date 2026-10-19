<?php
require_once __DIR__ . '/../repositories/UserRepository.php';

class AuthService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login(string $email, string $password): array
    {
        $usuario = $this->userRepo->findByEmail($email);

        if (!$usuario || !password_verify($password, $usuario->password)) {
            throw new Exception('Credenciales incorrectas', 401);
        }

        if ($usuario->activo === 0) {
            throw new Exception('El usuario se encuentra inactivo', 403);
        }

        $perfil = $usuario->toArrayWithoutPassword();
        $_SESSION['usuario'] = $perfil;
        return $perfil;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function getProfile(int $id): array
    {
        $usuario = $this->userRepo->findById($id);
        if (!$usuario) {
            throw new Exception('Usuario no encontrado', 404);
        }
        return $usuario->toArrayWithoutPassword();
    }
}
