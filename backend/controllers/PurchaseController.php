<?php
require_once __DIR__ . '/../services/PurchaseService.php';
require_once __DIR__ . '/../utils/Response.php';

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
