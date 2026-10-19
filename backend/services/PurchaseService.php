<?php
require_once __DIR__ . '/../repositories/PurchaseRepository.php';

class PurchaseService
{
    private PurchaseRepository $repo;

    public function __construct()
    {
        $this->repo = new PurchaseRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }
}
