<?php
require_once __DIR__ . '/../services/SalesService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middleware/auth.middleware.php';

class SalesController
{
    private SalesService $service;

    public function __construct()
    {
        $this->service = new SalesService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de ventas obtenido');
    }

    public function show(int $id): void
    {
        try {
            Response::success($this->service->obtener($id), 'Venta obtenida');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function checkout(): void
    {
        $input = Request::input();
        $usuario = currentUser();
        try {
            $resultado = $this->service->checkout(
                $input['items'] ?? [],
                $input['metodo_pago'] ?? 'Efectivo',
                (int) $usuario['id'],
                isset($input['cliente_id']) ? (int) $input['cliente_id'] : null
            );
            Response::success($resultado, 'Venta registrada correctamente', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function store(): void
    {
        $this->checkout();
    }
}
