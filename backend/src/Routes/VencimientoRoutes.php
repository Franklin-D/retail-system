<?php

use Retail\Controllers\VencimientoController;
use Retail\Utils\Response;

$controller = new VencimientoController();

if (($rest[0] ?? '') !== 'lots') {
    Response::error('Recurso de vencimiento no encontrado', 404);
}

$tieneId = isset($rest[1]);
$loteId = $tieneId && ctype_digit((string) $rest[1]) ? (int) $rest[1] : null;
$accion = $rest[2] ?? null;

if (!$tieneId && $method === 'GET') {
    $controller->index();
} elseif (!$tieneId && $method === 'POST') {
    $controller->store();
} elseif ($loteId !== null && $accion === 'discount' && $method === 'POST') {
    $controller->aplicarDescuento($loteId);
} else {
    Response::error('Método o parámetro no permitido para /expiry/lots', 405);
}
