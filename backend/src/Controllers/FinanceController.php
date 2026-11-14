<?php

namespace Retail\Controllers;

use Throwable;
use Retail\Middleware\AuthMiddleware;
use Retail\Services\FinanceService;
use Retail\Utils\Request;
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

    public function storeIngreso(): void
    {
        $usuario = AuthMiddleware::currentUser();
        try {
            $ingreso = $this->service->registrarIngreso(Request::input(), (int) $usuario['id']);
            Response::success($ingreso, 'Ingreso de caja registrado', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function storeEgreso(): void
    {
        $usuario = AuthMiddleware::currentUser();
        try {
            $egreso = $this->service->registrarEgreso(Request::input(), (int) $usuario['id']);
            Response::success($egreso, 'Egreso de caja registrado', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }
}
