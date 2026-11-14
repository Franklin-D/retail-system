<?php
include 'config.php';
require_once 'AuthMiddleware.php';
require_once 'AuditoriaLogService.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
$auditoria = new AuditoriaLogService($conn);

if ($action == 'listar') {
    AuthMiddleware::verificarSesion();
    AuthMiddleware::verificar2FA();

    $res = mysqli_query($conn, "SELECT * FROM productos ORDER BY id DESC");
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) { 
        $data[] = $row; 
    }
    echo json_encode(['status' => 'ok', 'data' => $data]);
}

if ($action == 'crear') {
    AuthMiddleware::verificarRol(['Administrador', 'Repositor']);
    AuthMiddleware::verificar2FA();

    $nombre = mysqli_real_escape_string($conn, $_POST['nombre'] ?? '');
    $codigo = mysqli_real_escape_string($conn, $_POST['codigo'] ?? '');
    $costo  = floatval($_POST['costo'] ?? 0);
    $precio = floatval($_POST['precio_venta'] ?? 0);
    $stock  = intval($_POST['stock'] ?? 0);
    
    $sql = "INSERT INTO productos (nombre, codigo, costo, precio_venta, stock) 
            VALUES ('$nombre', '$codigo', $costo, $precio, $stock)";
            
    if (mysqli_query($conn, $sql)) {
        $auditoria->registrar($_SESSION['user_id'], 'CREAR_PRODUCTO', "Producto: $nombre | Código: $codigo | Stock: $stock");
        echo json_encode(['status' => 'ok', 'msg' => 'Producto creado y auditado']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
    }
}

if ($action == 'eliminar') {
    AuthMiddleware::verificarRol('Administrador');
    AuthMiddleware::verificar2FA();

    $id = intval($_POST['id'] ?? 0);
    
    if (mysqli_query($conn, "DELETE FROM productos WHERE id = $id")) {
        $auditoria->registrar($_SESSION['user_id'], 'ELIMINAR_PRODUCTO', "Eliminado producto ID: $id");
        echo json_encode(['status' => 'ok', 'msg' => 'Producto eliminado']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => mysqli_error($conn)]);
    }
}

if ($action == 'ganancias') {
    AuthMiddleware::verificarRol('Administrador');
    AuthMiddleware::verificar2FA();

    $res = mysqli_query($conn, "SELECT SUM((precio_venta - costo) * stock) as ganancia_potencial, SUM(stock) as total_stock, COUNT(*) as total_productos FROM productos");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['status' => 'ok', 'data' => $row]);
}
?>