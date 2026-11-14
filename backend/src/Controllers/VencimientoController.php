<?php

namespace Retail\Controllers;

use Retail\Middleware\AuthMiddleware;
use Retail\Services\VencimientoService;
use Retail\Utils\Request;
use Retail\Utils\Response;
use Throwable;

class VencimientoController
{
    private const ROLES_INVENTARIO = ['administrador', 'repositor'];

    private VencimientoService $service;

    public function __construct()
    {
        $this->service = new VencimientoService();
    }

    public function index(): void
    {
        try {
            Response::success($this->service->listarProximosAVencer(), 'Lotes próximos a vencer');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function store(): void
    {
        try {
            AuthMiddleware::requireRole(self::ROLES_INVENTARIO);
            Response::success($this->service->crearLote(Request::input()), 'Lote registrado', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function aplicarDescuento(int $id): void
    {
        try {
            AuthMiddleware::requireRole(self::ROLES_INVENTARIO);
            Response::success(
                $this->service->aplicarDescuentoAutomatico($id),
                'Descuento automático aplicado al lote'
            );
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }
}
