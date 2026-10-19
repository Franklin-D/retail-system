<?php
require_once __DIR__ . '/../repositories/CustomerRepository.php';

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
