<?php

namespace Retail\Controllers;

use Throwable;
use Retail\Middleware\AuthMiddleware;
use Retail\Services\PurchaseService;
use Retail\Utils\Request;
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

    public function show(int $id): void
    {
        try {
            Response::success($this->service->obtener($id), 'Compra obtenida');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function store(): void
    {
        $usuario = AuthMiddleware::currentUser();
        try {
            $resultado = $this->service->registrar(Request::input(), (int) $usuario['id']);
            Response::success($resultado, 'Compra registrada y stock actualizado', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }
}
