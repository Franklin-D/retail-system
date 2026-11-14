<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthMiddleware {
    
    public static function verificarSesion() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'msg' => 'Acceso no autorizado. Debe iniciar sesión.']);
            exit();
        }
    }

    public static function verificarRol($rolesPermitidos) {
        self::verificarSesion();
        
        // Permite pasar un string ('Administrador') o un array (['Administrador', 'Repositor'])
        if (!is_array($rolesPermitidos)) {
            $rolesPermitidos = [$rolesPermitidos];
        }

        if (!isset($_SESSION['user_rol']) || !in_array($_SESSION['user_rol'], $rolesPermitidos)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado. Permisos insuficientes (RBAC).']);
            exit();
        }
    }

    public static function verificar2FA() {
        self::verificarSesion();
        
        if (isset($_SESSION['requires_2fa']) && $_SESSION['requires_2fa'] === true) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'msg' => 'Verificación de doble factor (2FA) pendiente.']);
            exit();
        }
    }
}
?>