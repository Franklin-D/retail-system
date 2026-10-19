<?php
/**
 * Formato único de respuesta JSON: { success, message, data }
 */
class Response
{
    public static function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, string $message = 'OK', int $code = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public static function error(string $message, int $code = 400, $data = null): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'error'   => $message,
            'data'    => $data,
        ], $code);
    }

    public static function fromException(Throwable $e): void
    {
        $code = (int) $e->getCode();
        if ($code === 422) {
            $details = json_decode($e->getMessage(), true);
            self::json([
                'success' => false,
                'message' => 'Errores de validación',
                'error'   => 'Errores de validación',
                'data'    => is_array($details) ? $details : $e->getMessage(),
            ], 422);
        }

        $http = ($code >= 400 && $code < 600) ? $code : 400;
        self::error($e->getMessage() ?: 'Error en la operación', $http);
    }
}
