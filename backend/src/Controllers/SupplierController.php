<?php

namespace Retail\Controllers;

use Retail\Services\SupplierService;
use Retail\Utils\Response;

class SupplierController
{
    private SupplierService $service;

    public function __construct()
    {
        $this->service = new SupplierService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de proveedores');
    }
}
