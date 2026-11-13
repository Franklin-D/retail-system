<?php

namespace Retail\Routes;

use Retail\Controllers\FinanceController;
use Retail\Utils\Response;

$controller = new FinanceController();
if ($method === 'GET') {
    $controller->egresos();
} elseif ($method === 'POST') {
    $controller->storeEgreso();
} else {
    Response::error('Método no permitido para /expenses', 405);
}
