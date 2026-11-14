<?php

namespace Retail\Routes;

use Retail\Controllers\PurchaseController;
use Retail\Utils\Response;

$controller = new PurchaseController();
$id = isset($rest[0]) && ctype_digit((string) $rest[0]) ? (int) $rest[0] : null;

if ($method === 'GET' && $id === null) {
    $controller->index();
} elseif ($method === 'GET' && $id !== null) {
    $controller->show($id);
} elseif ($method === 'POST' && $id === null) {
    $controller->store();
} else {
    Response::error('Método o parámetro no permitido para /purchases', 405);
}
