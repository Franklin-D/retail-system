<?php

namespace Retail\Routes;

use Retail\Controllers\SupplierController;
use Retail\Utils\Response;

$controller = new SupplierController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /suppliers', 405);
}
