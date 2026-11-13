<?php
include 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

if ($action == 'listar') {
    $res = mysqli_query($conn, "SELECT * FROM productos ORDER BY id DESC");
    $data = [];
    while($row = mysqli_fetch_assoc($res)){ $data[] = $row; }
    echo json_encode(['status'=>'ok', 'data'=>$data]);
}

if ($action == 'crear') {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $codigo = mysqli_real_escape_string($conn, $_POST['codigo']);
    $costo = floatval($_POST['costo']);
    $precio = floatval($_POST['precio_venta']);
    $stock = intval($_POST['stock']);
    
    $sql = "INSERT INTO productos (nombre, codigo, costo, precio_venta, stock) VALUES ('$nombre','$codigo',$costo,$precio,$stock)";
    if(mysqli_query($conn, $sql)){
        echo json_encode(['status'=>'ok', 'msg'=>'Producto creado']);
    } else {
        echo json_encode(['status'=>'error', 'msg'=>mysqli_error($conn)]);
    }
}

if ($action == 'eliminar') {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM productos WHERE id=$id");
    echo json_encode(['status'=>'ok']);
}

if ($action == 'ganancias') {
    $res = mysqli_query($conn, "SELECT SUM((precio_venta - costo) * stock) as ganancia_potencial, SUM(stock) as total_stock, COUNT(*) as total_productos FROM productos");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['status'=>'ok', 'data'=>$row]);
}
?>