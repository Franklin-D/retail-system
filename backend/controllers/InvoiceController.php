<?php
require_once __DIR__ . '/../services/InvoiceService.php';
require_once __DIR__ . '/../utils/Response.php';

class InvoiceController
{
    private InvoiceService $service;

    public function __construct()
    {
        $this->service = new InvoiceService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de facturas');
    }
}
