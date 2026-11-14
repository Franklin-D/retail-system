<?php

namespace Retail\Services;

use Exception;
use Retail\Repositories\ExpenseRepository;
use Retail\Repositories\IncomeRepository;

class FinanceService
{
    private const METODOS_PAGO = ['Efectivo', 'Tarjeta', 'Yape', 'Plin'];

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

    /** RF-2.1: ingreso extraordinario de caja (no asociado a una venta). */
    public function registrarIngreso(array $data, int $usuarioId): array
    {
        $monto = $this->montoValido($data);
        $motivo = $this->texto($data['motivo'] ?? null);
        $descripcion = $this->texto($data['descripcion'] ?? null) ?? $motivo;
        if ($descripcion === null) {
            throw new Exception('La descripción o motivo del ingreso es obligatorio', 400);
        }

        $metodo = $data['metodo_pago'] ?? 'Efectivo';
        if (!in_array($metodo, self::METODOS_PAGO, true)) {
            throw new Exception('Método de pago no válido', 400);
        }

        $id = $this->ingresos->registrarManual([
            'descripcion' => $descripcion,
            'motivo'      => $motivo,
            'comprobante' => $this->texto($data['comprobante'] ?? null),
            'monto'       => $monto,
            'metodo_pago' => $metodo,
        ], $usuarioId);

        return $this->ingresos->findById($id) ?? [];
    }

    /** RF-2.2: egreso extraordinario de caja (no asociado a una compra). */
    public function registrarEgreso(array $data, int $usuarioId): array
    {
        $monto = $this->montoValido($data);
        $motivo = $this->texto($data['motivo'] ?? null) ?? 'Otro';
        $descripcion = $this->texto($data['descripcion'] ?? null) ?? $motivo;

        $id = $this->egresos->registrarManual([
            'descripcion' => $descripcion,
            'monto'       => $monto,
            'motivo'      => $motivo,
            'comprobante' => $this->texto($data['comprobante'] ?? null),
        ], $usuarioId);

        return $this->egresos->findById($id) ?? [];
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

    private function montoValido(array $data): float
    {
        $monto = (float) ($data['monto'] ?? 0);
        if ($monto <= 0) {
            throw new Exception('El monto debe ser mayor a cero', 400);
        }
        return round($monto, 2);
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));
        return $texto === '' ? null : $texto;
    }
}
