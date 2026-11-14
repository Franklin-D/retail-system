<?php

namespace Retail\Models;

class MovimientoInventario
{
    public ?int $id;
    public int $producto_id;
    public int $usuario_id;
    public ?string $fecha;
    public string $tipo;
    public int $cantidad;
    public int $stock_anterior;
    public int $stock_nuevo;
    public string $origen;
    public ?int $referencia_id;
    public ?string $motivo;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->producto_id = (int) ($data['producto_id'] ?? 0);
        $this->usuario_id = (int) ($data['usuario_id'] ?? 0);
        $this->fecha = $data['fecha'] ?? null;
        $this->tipo = $data['tipo'] ?? 'ajuste';
        $this->cantidad = (int) ($data['cantidad'] ?? 0);
        $this->stock_anterior = (int) ($data['stock_anterior'] ?? 0);
        $this->stock_nuevo = (int) ($data['stock_nuevo'] ?? 0);
        $this->origen = $data['origen'] ?? 'ajuste';
        $this->referencia_id = isset($data['referencia_id']) ? (int) $data['referencia_id'] : null;
        $this->motivo = $data['motivo'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'producto_id'    => $this->producto_id,
            'usuario_id'     => $this->usuario_id,
            'fecha'          => $this->fecha,
            'tipo'           => $this->tipo,
            'cantidad'       => $this->cantidad,
            'stock_anterior' => $this->stock_anterior,
            'stock_nuevo'    => $this->stock_nuevo,
            'origen'         => $this->origen,
            'referencia_id'  => $this->referencia_id,
            'motivo'         => $this->motivo,
        ];
    }
}
