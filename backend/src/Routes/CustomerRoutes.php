<?php

namespace Retail\Routes;

use Retail\Controllers\CustomerController;
use Retail\Utils\Response;

$controller = new CustomerController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /customers', 405);
}
