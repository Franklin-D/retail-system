<?php
include 'config.php';
require_once 'AuditoriaLogService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$auditoria = new AuditoriaLogService($conn);

if ($action == 'login') {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT u.id, u.username, u.password_hash, u.secret_2fa, r.nombre as rol 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.username = '$username' AND u.activo = 1 LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($res)) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_rol'] = $row['rol'];
            
            // Si el usuario tiene 2FA configurado, marcar como pendiente
            if (!empty($row['secret_2fa'])) {
                $_SESSION['requires_2fa'] = true;
                $_SESSION['temp_secret_2fa'] = $row['secret_2fa'];
                $auditoria->registrar($row['id'], 'LOGIN_PASO_1', 'Credenciales válidas, esperando 2FA.');
                echo json_encode(['status' => '2fa_required', 'msg' => 'Ingrese su código de verificación 2FA.']);
            } else {
                $_SESSION['requires_2fa'] = false;
                $auditoria->registrar($row['id'], 'LOGIN_EXITOSO', 'Inicio de sesión sin 2FA.');
                echo json_encode(['status' => 'ok', 'msg' => 'Bienvenido', 'rol' => $row['rol']]);
            }
            exit();
        }
    }

    $auditoria->registrar(0, 'LOGIN_FALLIDO', "Intento fallido para usuario: $username");
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => 'Usuario o contraseña incorrectos.']);
}

if ($action == 'verificar_2fa') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'msg' => 'Sesión no iniciada.']);
        exit();
    }

    $codigo = $_POST['codigo_2fa'] ?? '';
    if ($codigo === ($_SESSION['temp_secret_2fa'] ?? '')) {
        $_SESSION['requires_2fa'] = false;
        unset($_SESSION['temp_secret_2fa']);
        $auditoria->registrar($_SESSION['user_id'], '2FA_VERIFICADO', 'Doble factor validado correctamente.');
        echo json_encode(['status' => 'ok', 'msg' => 'Autenticación completa.', 'rol' => $_SESSION['user_rol']]);
    } else {
        $auditoria->registrar($_SESSION['user_id'], '2FA_FALLIDO', 'Código 2FA inválido.');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'msg' => 'Código 2FA incorrecto.']);
    }
}

if ($action == 'logout') {
    if (isset($_SESSION['user_id'])) {
        $auditoria->registrar($_SESSION['user_id'], 'LOGOUT', 'Cierre de sesión manual.');
    }
    session_destroy();
    echo json_encode(['status' => 'ok', 'msg' => 'Sesión cerrada.']);
}
?>