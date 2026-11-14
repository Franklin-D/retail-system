<?php
session_start();
require_once 'conexion.php'; // Tu conexión mysqli

$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. Buscar usuario en la base de datos usando consultas preparadas
    $stmt = $conn->prepare("SELECT id, username, password_hash, rol_id, secret_2fa FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // 2. Verificar la contraseña principal con password_verify
        if (password_verify($password, $row['password_hash'])) {
            
            // 3. Generar un código temporal seguro para el 2FA (válido por 5 minutos)
            $codigoTemporal = rand(100000, 999999);
            
            $_SESSION['temp_user_id'] = $row['id'];
            $_SESSION['temp_rol_id'] = $row['rol_id'];
            $_SESSION['codigo_2fa_generado'] = $codigoTemporal;
            $_SESSION['2fa_expira'] = time() + 300; // Expira en 300 segundos (5 min)
            $_SESSION['requires_2fa'] = true;

            // Para pruebas locales (XAMPP), registramos el código en el error_log o lo simulamos
            error_log("Código 2FA generado para {$username}: " . $codigoTemporal);

            echo json_encode([
                "status" => "2fa_required",
                "message" => "Credenciales correctas. Ingrese el código 2FA.",
                "debug_codigo_2fa" => $codigoTemporal // Opcional para pruebas locales rápidas
            ]);
            exit;
        }
    }

    echo json_encode(["status" => "error", "message" => "Credenciales inválidas"]);
    exit;
}

if ($action === 'verificar_2fa') {
    $codigoIngresado = $_POST['codigo_2fa'] ?? '';

    // Validar que exista una sesión pendiente de 2FA
    if (!isset($_SESSION['requires_2fa']) || $_SESSION['requires_2fa'] !== true) {
        echo json_encode(["status" => "error", "message" => "No hay sesión pendiente de validación 2FA"]);
        exit;
    }

    // Validar expiración del código
    if (time() > $_SESSION['2fa_expira']) {
        echo json_encode(["status" => "error", "message" => "El código 2FA ha expirado"]);
        exit;
    }

    // 4. Comparar el código ingresado con el código temporal generado (¡NUNCA contra el secreto maestro!)
    if (hash_equals((string)$_SESSION['codigo_2fa_generado'], (string)$codigoIngresado)) {
        // Autenticación exitosa completa
        $_SESSION['user_id'] = $_SESSION['temp_user_id'];
        $_SESSION['user_rol_id'] = $_SESSION['temp_rol_id'];
        $_SESSION['requires_2fa'] = false;

        // Limpiar variables temporales de seguridad
        unset($_SESSION['temp_user_id'], $_SESSION['temp_rol_id'], $_SESSION['codigo_2fa_generado'], $_SESSION['2fa_expira']);

        echo json_encode(["status" => "success", "message" => "Autenticación 2FA exitosa"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Código 2FA incorrecto"]);
    }
    exit;
}
?>