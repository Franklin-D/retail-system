<?php

namespace Retail\Middleware;

use Retail\Utils\Response;

class AuthMiddleware
{
    public static function requireAuth(): array
    {
        if (empty($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
            Response::error('No autenticado', 401);
        }
        return $_SESSION['usuario'];
    }

    public static function currentUser(): array
    {
        return self::requireAuth();
    }

    public static function requireRole(array $rolesPermitidos, ?array $usuario = null): void
    {
        $usuario = $usuario ?? self::currentUser();
        $rol = $usuario['rol'] ?? '';
        if (!in_array($rol, $rolesPermitidos, true)) {
            Response::error('Acceso denegado: permisos insuficientes', 403);
        }
    }
}
