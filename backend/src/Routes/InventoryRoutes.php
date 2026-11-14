<?php

namespace Retail\Routes;

use Retail\Controllers\InventoryController;
use Retail\Utils\Response;

$controller = new InventoryController();
$accion = $rest[0] ?? '';
$id = isset($rest[1]) && ctype_digit((string) $rest[1]) ? (int) $rest[1] : null;

if ($method !== 'GET') {
    Response::error('Método no permitido para /inventory', 405);
}

if ($accion === '' || $accion === 'movements') {
    $controller->movements();
} elseif ($accion === 'kardex' && $id !== null) {
    $_GET['producto_id'] = $id;
    $controller->movements();
} else {
    Response::error('Ruta no encontrada en /inventory', 404);
}
