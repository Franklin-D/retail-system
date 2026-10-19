<?php
require_once __DIR__ . '/../controllers/SalesController.php';

$controller = new SalesController();
$id = isset($rest[0]) && ctype_digit((string) $rest[0]) ? (int) $rest[0] : null;
$accion = $rest[0] ?? '';

if ($method === 'GET' && $id === null && $accion === '') {
    $controller->index();
} elseif ($method === 'GET' && $id !== null) {
    $controller->show($id);
} elseif ($method === 'POST' && $accion === 'checkout') {
    $controller->checkout();
} elseif ($method === 'POST' && $accion === '') {
    $controller->store();
} else {
    Response::error('Método o parámetro no permitido para /sales', 405);
}
