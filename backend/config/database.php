<?php
/**
 * Única conexión PDO a MySQL (consultas preparadas).
 */
require_once __DIR__ . '/env.php';

class Database
{
    private static ?PDO $conexion = null;

    public static function getConnection(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $host = env('DB_HOST', 'localhost');
        $name = env('DB_NAME', 'planet_color');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$conexion = new PDO($dsn, $user, $pass, $opciones);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error'   => 'Error de conexión a la base de datos',
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return self::$conexion;
    }
}
