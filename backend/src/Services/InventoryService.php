<?php

namespace Retail\Services;

use Retail\Repositories\InventoryMovementRepository;

class InventoryService
{
    private InventoryMovementRepository $movimientos;

    public function __construct()
    {
        $this->movimientos = new InventoryMovementRepository();
    }

    public function movimientos(array $filtros = []): array
    {
        return $this->movimientos->findAll($filtros);
    }
}
