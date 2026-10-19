<?php
/**
 * Lectura del cuerpo de la petición (JSON o formulario).
 */
class Request
{
    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        return is_array($data) ? $data : [];
    }

    public static function input(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }
        return self::json();
    }
}
