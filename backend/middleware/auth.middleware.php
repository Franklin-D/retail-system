<?php
/**
 * Autenticación por sesión PHP (cookie httpOnly), no JWT.
 */

require_once __DIR__ . '/../utils/Response.php';

function requireAuth(): array
{
    if (empty($_SESSION['usuario']) || empty($_SESSION['usuario']['id'])) {
        Response::error('No autenticado', 401);
    }
    return $_SESSION['usuario'];
}

function currentUser(): array
{
    return requireAuth();
}

function requireRole(array $rolesPermitidos, ?array $usuario = null): void
{
    $usuario = $usuario ?? currentUser();
    $rol = $usuario['rol'] ?? '';
    if (!in_array($rol, $rolesPermitidos, true)) {
        Response::error('Acceso denegado: permisos insuficientes', 403);
    }
}
