<?php

namespace Retail\Routes;

use Retail\Controllers\ReportController;
use Retail\Utils\Response;

$controller = new ReportController();
$accion = $rest[0] ?? '';

if ($method !== 'GET') {
    Response::error('Método no permitido para /reports', 405);
}

if ($accion === '') {
    $controller->index();
} elseif ($accion === 'summary') {
    $controller->summary();
} elseif ($accion === 'inventory') {
    $controller->inventory();
} elseif ($accion === 'profits') {
    $controller->profits();
} else {
    Response::error('Ruta no encontrada en /reports', 404);
}
