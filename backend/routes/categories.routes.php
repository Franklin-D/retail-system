<?php
require_once __DIR__ . '/../controllers/CategoryController.php';

$controller = new CategoryController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /categories', 405);
}
