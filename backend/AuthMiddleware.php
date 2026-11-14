<?php
class AuthMiddleware {

    public static function verificarSesion() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "No autorizado. Inicie sesión."]);
            exit;
        }
    }

    public static function verificar2FA() {
        // Si la sesión requiere completar el 2FA o está pendiente
        if (isset($_SESSION['requires_2fa']) && $_SESSION['requires_2fa'] === true) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Acceso bloqueado. Debe completar la validación 2FA."]);
            exit;
        }
    }

    public static function verificarRol($rolesPermitidos) {
        self::verificarSesion();
        
        // $rolesPermitidos puede ser un string ('Administrador') o un array (['Administrador', 'Repositor'])
        if (!is_array($rolesPermitidos)) {
            $rolesPermitidos = [$rolesPermitidos];
        }

        // Suponiendo que en la sesión guardas el nombre del rol o su ID
        $rolUsuario = $_SESSION['user_rol'] ?? ''; // o rol_id

        if (!in_array($rolUsuario, $rolesPermitidos)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Acceso denegado. Rol sin permisos suficientes."]);
            exit;
        }
    }
}
?>