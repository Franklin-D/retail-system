<?php
/**
 * middleware/cors.middleware.php
 * -----------------------------------------------------------------------
 * Responsabilidad: permitir que el frontend (servido en otro puerto
 * durante desarrollo) pueda llamar a la API sin ser bloqueado por CORS,
 * y responder correctamente a las peticiones preflight (OPTIONS).
 *
 * Implementación real: ETAPA 5.
 * -----------------------------------------------------------------------
 */

function applyCors(): void
{
    // TODO (Etapa 5):
    // header('Access-Control-Allow-Origin: ' . env('CORS_ALLOWED_ORIGIN'));
    // header('Access-Control-Allow-Credentials: true');
    // header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    // header('Access-Control-Allow-Headers: Content-Type, Authorization');
    // if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}
