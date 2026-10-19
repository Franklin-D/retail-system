<?php
require_once __DIR__ . '/../controllers/CustomerController.php';

$controller = new CustomerController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /customers', 405);
}
