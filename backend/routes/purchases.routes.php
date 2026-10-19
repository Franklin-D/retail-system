<?php
require_once __DIR__ . '/../controllers/PurchaseController.php';

$controller = new PurchaseController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /purchases', 405);
}
