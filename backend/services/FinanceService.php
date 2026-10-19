<?php
require_once __DIR__ . '/../repositories/IncomeRepository.php';
require_once __DIR__ . '/../repositories/ExpenseRepository.php';

class FinanceService
{
    private IncomeRepository $ingresos;
    private ExpenseRepository $egresos;

    public function __construct()
    {
        $this->ingresos = new IncomeRepository();
        $this->egresos = new ExpenseRepository();
    }

    public function listarIngresos(): array
    {
        return $this->ingresos->findAll();
    }

    public function listarEgresos(): array
    {
        return $this->egresos->findAll();
    }

    public function movimientos(): array
    {
        $filas = [];
        foreach ($this->ingresos->findAll() as $ingreso) {
            $filas[] = $ingreso;
        }
        foreach ($this->egresos->findAll() as $egreso) {
            $filas[] = $egreso;
        }
        usort($filas, fn($a, $b) => strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? '')));
        return $filas;
    }
}
