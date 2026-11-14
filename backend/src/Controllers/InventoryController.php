<?php

namespace Retail\Controllers;

use Retail\Services\InventoryService;
use Retail\Utils\Response;

class InventoryController
{
    private InventoryService $service;

    public function __construct()
    {
        $this->service = new InventoryService();
    }

    public function movements(): void
    {
        $filtros = [
            'producto_id' => $_GET['producto_id'] ?? null,
            'tipo'        => $_GET['tipo'] ?? null,
            'origen'      => $_GET['origen'] ?? null,
            'desde'       => $_GET['desde'] ?? null,
            'hasta'       => $_GET['hasta'] ?? null,
            'limite'      => $_GET['limite'] ?? null,
        ];
        Response::success($this->service->movimientos($filtros), 'Kardex de inventario');
    }
}
