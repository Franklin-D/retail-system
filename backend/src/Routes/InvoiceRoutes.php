<?php

namespace Retail\Routes;

use Retail\Controllers\InvoiceController;
use Retail\Utils\Response;

$controller = new InvoiceController();
if ($method === 'GET') {
    $controller->index();
} else {
    Response::error('Método no permitido para /invoices', 405);
}
