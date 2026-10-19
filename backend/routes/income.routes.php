<?php
require_once __DIR__ . '/../controllers/FinanceController.php';

$controller = new FinanceController();
if ($method === 'GET') {
    $controller->ingresos();
} else {
    Response::error('Método no permitido para /income', 405);
}
