<?php

namespace Retail\Config;

use PDO;
use PDOException;

/**
 * Única conexión PDO a MySQL (consultas preparadas).
 */

class Database
{
    private static ?PDO $conexion = null;

    public static function getConnection(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $host = Env::get('DB_HOST', 'localhost');
        $name = Env::get('DB_NAME', 'planet_color');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

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
