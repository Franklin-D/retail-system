<?php

namespace Retail\Services;

use Retail\Repositories\SupplierRepository;

class SupplierService
{
    private SupplierRepository $repo;

    public function __construct()
    {
        $this->repo = new SupplierRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }
}
