<?php
class Venta
{
    public ?int $id;
    public ?int $cliente_id;
    public int $usuario_id;
    public ?string $fecha;
    public string $motivo;
    public string $metodo_pago;
    public float $total;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->cliente_id = isset($data['cliente_id']) && $data['cliente_id'] !== ''
            ? (int) $data['cliente_id'] : null;
        $this->usuario_id = (int) ($data['usuario_id'] ?? 0);
        $this->fecha = $data['fecha'] ?? null;
        $this->motivo = $data['motivo'] ?? 'Venta';
        $this->metodo_pago = $data['metodo_pago'] ?? 'Efectivo';
        $this->total = (float) ($data['total'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'cliente_id'  => $this->cliente_id,
            'usuario_id'  => $this->usuario_id,
            'fecha'       => $this->fecha,
            'motivo'      => $this->motivo,
            'metodo_pago' => $this->metodo_pago,
            'total'       => $this->total,
        ];
    }
}
