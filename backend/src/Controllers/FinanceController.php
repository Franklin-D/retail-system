<?php

namespace Retail\Controllers;

use Retail\Services\FinanceService;
use Retail\Utils\Response;

class FinanceController
{
    private FinanceService $service;

    public function __construct()
    {
        $this->service = new FinanceService();
    }

    public function ingresos(): void
    {
        Response::success($this->service->listarIngresos(), 'Listado de ingresos');
    }

    public function egresos(): void
    {
        Response::success($this->service->listarEgresos(), 'Listado de egresos');
    }

    public function movimientos(): void
    {
        Response::success($this->service->movimientos(), 'Movimientos financieros');
    }
}
