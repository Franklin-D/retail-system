<?php
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../utils/Response.php';

class CustomerController
{
    private CustomerService $service;

    public function __construct()
    {
        $this->service = new CustomerService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de clientes');
    }
}
