<?php
/**
 * // backend/utils/Response.php
 * -----------------------------------------------------------------------
 * Responsabilidad: centralizar el formato de TODAS las respuestas JSON de
 * la API, para que sean consistentes en toda la aplicación:
 *
 *   { "success": true|false, "message": "...", "data": {...} }
 *
 * Implementación real: ETAPA 5.
 * -----------------------------------------------------------------------
 */
class Response {
    public static function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = null, string $message = 'OK', int $code = 200): void {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    public static function error(string $message, int $code = 400): void {
        self::json([
            'success' => false,
            'error'   => $message
        ], $code);
    }
}
