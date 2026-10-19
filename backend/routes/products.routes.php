<?php
require_once __DIR__ . '/../controllers/ProductController.php';

$controller = new ProductController();
$id = isset($rest[0]) && ctype_digit((string) $rest[0]) ? (int) $rest[0] : null;

if ($method === 'GET' && $id === null) {
    $controller->index();
} elseif ($method === 'GET' && $id !== null) {
    $controller->show($id);
} elseif ($method === 'POST' && $id === null) {
    $controller->store();
} elseif (($method === 'PUT' || $method === 'POST') && $id !== null) {
    $controller->update($id);
} elseif ($method === 'DELETE' && $id !== null) {
    $controller->destroy($id);
} else {
    Response::error('Método o parámetro no permitido para /products', 405);
}
