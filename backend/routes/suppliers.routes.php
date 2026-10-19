<?php
require_once __DIR__ . '/../controllers/SupplierController.php';

$controller = new SupplierController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /suppliers', 405);
}
