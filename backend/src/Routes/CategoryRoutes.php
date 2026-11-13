<?php

namespace Retail\Routes;

use Retail\Controllers\CategoryController;
use Retail\Utils\Response;

$controller = new CategoryController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /categories', 405);
}
