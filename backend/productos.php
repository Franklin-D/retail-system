<?php
// backend/productos.php
require_once 'conexion_pdo.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

try {
    // 1. LISTAR PRODUCTOS + CATEGORÍA + UBICACIÓN + ALERTA DE STOCK MÍNIMO
    if ($action === 'listar') {
        $sql = "SELECT 
                    p.*,
                    c.nombre AS categoria_nombre,
                    u.pasillo,
                    u.estante,
                    CASE WHEN p.stock < p.stock_minimo THEN 1 ELSE 0 END AS alerta_stock_bajo
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN ubicacion_estante u ON p.ubicacion_id = u.id
                ORDER BY p.id DESC";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();

        echo json_encode(['status' => 'ok', 'data' => $data]);
        exit;
    }

    // LISTAR CATEGORÍAS Y UBICACIONES PARA LOS SELECTS DEL FRONTEND
    if ($action === 'catalogos') {
        $categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC")->fetchAll();
        $ubicaciones = $pdo->query("SELECT * FROM ubicacion_estante ORDER BY pasillo, estante ASC")->fetchAll();
        echo json_encode([
            'status' => 'ok',
            'categorias' => $categorias,
            'ubicaciones' => $ubicaciones
        ]);
        exit;
    }

    // 2. CREAR PRODUCTO (CON VALIDACIÓN DE CÓDIGO DE BARRAS ÚNICO)
    if ($action === 'crear') {
        $nombre       = trim($_POST['nombre'] ?? '');
        $codigo       = trim($_POST['codigo'] ?? '');
        $costo        = floatval($_POST['costo'] ?? 0);
        $precio       = floatval($_POST['precio_venta'] ?? 0);
        $stock        = intval($_POST['stock'] ?? 0);
        $stock_minimo = intval($_POST['stock_minimo'] ?? 5);
        $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        $ubicacion_id = !empty($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : null;

        if ($nombre === '' || $codigo === '') {
            echo json_encode(['status' => 'error', 'msg' => 'El nombre y el código de barras son obligatorios.']);
            exit;
        }

        // Validar código de barras único
        $check = $pdo->prepare("SELECT id FROM productos WHERE codigo = :codigo LIMIT 1");
        $check->execute([':codigo' => $codigo]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'msg' => 'El código de barras ya está registrado en otro producto.']);
            exit;
        }

        $sql = "INSERT INTO productos (nombre, codigo, costo, precio_venta, stock, stock_minimo, categoria_id, ubicacion_id)
                VALUES (:nombre, :codigo, :costo, :precio, :stock, :stock_minimo, :categoria_id, :ubicacion_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'       => $nombre,
            ':codigo'       => $codigo,
            ':costo'        => $costo,
            ':precio'       => $precio,
            ':stock'        => $stock,
            ':stock_minimo' => $stock_minimo,
            ':categoria_id' => $categoria_id,
            ':ubicacion_id' => $ubicacion_id
        ]);

        echo json_encode(['status' => 'ok', 'msg' => 'Producto creado correctamente.']);
        exit;
    }

    // 3. ACTUALIZAR / EDITAR PRODUCTO (CRUD COMPLETO)
    if ($action === 'editar') {
        $id           = intval($_POST['id'] ?? 0);
        $nombre       = trim($_POST['nombre'] ?? '');
        $codigo       = trim($_POST['codigo'] ?? '');
        $costo        = floatval($_POST['costo'] ?? 0);
        $precio       = floatval($_POST['precio_venta'] ?? 0);
        $stock        = intval($_POST['stock'] ?? 0);
        $stock_minimo = intval($_POST['stock_minimo'] ?? 5);
        $categoria_id = !empty($_POST['categoria_id']) ? intval($_POST['categoria_id']) : null;
        $ubicacion_id = !empty($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : null;

        if ($id <= 0 || $nombre === '' || $codigo === '') {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos para actualizar el producto.']);
            exit;
        }

        // Validar que el código no pertenezca a otro producto diferente
        $check = $pdo->prepare("SELECT id FROM productos WHERE codigo = :codigo AND id != :id LIMIT 1");
        $check->execute([':codigo' => $codigo, ':id' => $id]);
        if ($check->fetch()) {
            echo json_encode(['status' => 'error', 'msg' => 'Otro producto ya utiliza ese código de barras.']);
            exit;
        }

        $sql = "UPDATE productos SET 
                    nombre = :nombre,
                    codigo = :codigo,
                    costo = :costo,
                    precio_venta = :precio,
                    stock = :stock,
                    stock_minimo = :stock_minimo,
                    categoria_id = :categoria_id,
                    ubicacion_id = :ubicacion_id
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'       => $nombre,
            ':codigo'       => $codigo,
            ':costo'        => $costo,
            ':precio'       => $precio,
            ':stock'        => $stock,
            ':stock_minimo' => $stock_minimo,
            ':categoria_id' => $categoria_id,
            ':ubicacion_id' => $ubicacion_id,
            ':id'           => $id
        ]);

        echo json_encode(['status' => 'ok', 'msg' => 'Producto actualizado correctamente.']);
        exit;
    }

    // 4. ELIMINAR PRODUCTO
    if ($action === 'eliminar') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['status' => 'ok', 'msg' => 'Producto eliminado.']);
        exit;
    }

    // 5. GANANCIAS (COMPATIBILIDAD CON CÓDIGO EXISTENTE DEL EQUIPO)
    if ($action === 'ganancias') {
        $sql = "SELECT 
                    SUM((precio_venta - costo) * stock) AS ganancia_potencial,
                    SUM(stock) AS total_stock,
                    COUNT(*) AS total_productos,
                    SUM(CASE WHEN stock < stock_minimo THEN 1 ELSE 0 END) AS productos_stock_bajo
                FROM productos";
        $row = $pdo->query($sql)->fetch();
        echo json_encode(['status' => 'ok', 'data' => $row]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
