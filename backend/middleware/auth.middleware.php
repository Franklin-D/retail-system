<?php
/**
 * middleware/auth.middleware.php
 * Protección de endpoints vía Token JWT.
 */

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

function requireAuth(): array
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        Response::error('No autenticado: Token no proporcionado', 401);
    }

    $token = $matches[1];
    $payload = JWT::verify($token);

    if (!$payload) {
        Response::error('No autenticado: Token inválido o expirado', 401);
    }

    return $payload; // Retorna la información del payload cargado en el JWT
}

function requireRole(array $allowedRoles, array $userPayload): void
{
    if (!in_array($userPayload['rol'] ?? '', $allowedRoles, true)) {
        Response::error('Acceso denegado: Permisos insuficientes', 403);
    }
}