<?php
require_once __DIR__ . '/../services/SupplierService.php';
require_once __DIR__ . '/../utils/Response.php';

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
