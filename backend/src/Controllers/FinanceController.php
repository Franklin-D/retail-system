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

    public function dashboard(): void
    {
        try {
            $desde = $_GET['desde'] ?? null;
            $hasta = $_GET['hasta'] ?? null;
            $categoriaId = $_GET['categoria_id'] ?? null;

            Response::success(
                $this->service->dashboard($desde, $hasta, $categoriaId),
                'Dashboard financiero obtenido'
            );
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function storeEgreso(): void
    {
        $input = Request::input();
        $usuario = AuthMiddleware::currentUser();

        try {
            $egreso = $this->service->registrarEgreso($input, (int) $usuario['id']);
            Response::success($egreso, 'Egreso registrado correctamente', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }
}
