<?php
class Ingreso
{
    public ?int $id;
    public ?string $fecha;
    public string $descripcion;
    public float $monto;
    public string $metodo_pago;
    public ?int $venta_id;
    public int $usuario_id;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->fecha = $data['fecha'] ?? null;
        $this->descripcion = $data['descripcion'] ?? '';
        $this->monto = (float) ($data['monto'] ?? 0);
        $this->metodo_pago = $data['metodo_pago'] ?? 'Efectivo';
        $this->venta_id = isset($data['venta_id']) ? (int) $data['venta_id'] : null;
        $this->usuario_id = (int) ($data['usuario_id'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'fecha'       => $this->fecha,
            'descripcion' => $this->descripcion,
            'monto'       => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'venta_id'    => $this->venta_id,
            'usuario_id'  => $this->usuario_id,
            'tipo'        => 'ingreso',
        ];
    }
}
