<?php

namespace Retail\Services;

use Retail\Repositories\CustomerRepository;

class CustomerService
{
    private CustomerRepository $repo;

    public function __construct()
    {
        $this->repo = new CustomerRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }
}
