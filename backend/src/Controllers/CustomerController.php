<?php

namespace Retail\Controllers;

use Retail\Services\CustomerService;
use Retail\Utils\Response;

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
