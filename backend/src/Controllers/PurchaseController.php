<?php

namespace Retail\Controllers;

use Retail\Services\PurchaseService;
use Retail\Utils\Response;

class PurchaseController
{
    private PurchaseService $service;

    public function __construct()
    {
        $this->service = new PurchaseService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de compras');
    }
}
