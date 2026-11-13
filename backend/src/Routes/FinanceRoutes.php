<?php

namespace Retail\Routes;

use Retail\Controllers\FinanceController;
use Retail\Utils\Response;

$controller = new FinanceController();
$accion = $rest[0] ?? '';

if ($method === 'GET' && ($accion === '' || $accion === 'dashboard')) {
    $controller->dashboard();
} elseif ($method === 'GET' && $accion === 'movimientos') {
    $controller->movimientos();
} else {
    Response::error('Método o parámetro no permitido para /finance', 405);
}
