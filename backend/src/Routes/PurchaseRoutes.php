<?php

namespace Retail\Routes;

use Retail\Controllers\PurchaseController;
use Retail\Utils\Response;

$controller = new PurchaseController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /purchases', 405);
}
