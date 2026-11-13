<?php
// backend/facturacion.php
require_once 'conexion_pdo.php';
require_once 'FacturacionElectronicaService.php';

header('Content-Type: application/json; charset=utf-8');

$service = new FacturacionElectronicaService($pdo);
$action  = $_GET['action'] ?? $_POST['action'] ?? 'listar';

try {
    if ($action === 'emitir') {
        $ventaId         = intval($_POST['venta_id'] ?? 0);
        $tipoComprobante = $_POST['tipo_comprobante'] ?? 'BOLETA';
        $tipoDoc         = $_POST['tipo_documento_cliente'] ?? 'DNI';
        $numeroDoc       = $_POST['numero_documento_cliente'] ?? '';
        $nombreCliente   = $_POST['nombre_cliente'] ?? '';

        $comprobante = $service->emitirComprobante(
            $ventaId,
            $tipoComprobante,
            $tipoDoc,
            $numeroDoc,
            $nombreCliente
        );

        echo json_encode(['status' => 'ok', 'msg' => 'Comprobante emitido con éxito.', 'data' => $comprobante]);
        exit;
    }

    if ($action === 'detalle') {
        $id = intval($_GET['id'] ?? 0);
        $comprobante = $service->obtenerComprobante($id);
        echo json_encode(['status' => 'ok', 'data' => $comprobante]);
        exit;
    }

    if ($action === 'listar') {
        $stmt = $pdo->query("SELECT * FROM comprobantes ORDER BY id DESC");
        echo json_encode(['status' => 'ok', 'data' => $stmt->fetchAll()]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
