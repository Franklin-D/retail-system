<?php
require_once __DIR__ . '/../controllers/FinanceController.php';

$controller = new FinanceController();
if ($method === 'GET') {
    $controller->egresos();
} else {
    Response::error('Método no permitido para /expenses', 405);
}
