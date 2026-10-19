<?php
require_once __DIR__ . '/../controllers/InvoiceController.php';

$controller = new InvoiceController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /invoices', 405);
}
