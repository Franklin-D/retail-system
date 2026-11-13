<?php

namespace Retail\Controllers;

use Retail\Services\InvoiceService;
use Retail\Utils\Response;

class InvoiceController
{
    private InvoiceService $service;

    public function __construct()
    {
        $this->service = new InvoiceService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de facturas');
    }
}
