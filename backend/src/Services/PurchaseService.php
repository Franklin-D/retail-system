<?php

namespace Retail\Services;

use Retail\Repositories\PurchaseRepository;

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
