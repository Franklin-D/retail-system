<?php
class Egreso
{
    public ?int $id;
    public ?string $fecha;
    public string $descripcion;
    public float $monto;
    public string $motivo;
    public ?int $compra_id;
    public int $usuario_id;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->fecha = $data['fecha'] ?? null;
        $this->descripcion = $data['descripcion'] ?? '';
        $this->monto = (float) ($data['monto'] ?? 0);
        $this->motivo = $data['motivo'] ?? 'Otro';
        $this->compra_id = isset($data['compra_id']) ? (int) $data['compra_id'] : null;
        $this->usuario_id = (int) ($data['usuario_id'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'fecha'       => $this->fecha,
            'descripcion' => $this->descripcion,
            'monto'       => $this->monto,
            'motivo'      => $this->motivo,
            'compra_id'   => $this->compra_id,
            'usuario_id'  => $this->usuario_id,
            'tipo'        => 'egreso',
        ];
    }
}
