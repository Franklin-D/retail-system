<?php
require_once __DIR__ . '/../repositories/SupplierRepository.php';

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
