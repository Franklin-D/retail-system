<?php
class Compra
{
    public ?int $id;
    public int $proveedor_id;
    public int $usuario_id;
    public ?string $fecha;
    public float $total;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->proveedor_id = (int) ($data['proveedor_id'] ?? 0);
        $this->usuario_id = (int) ($data['usuario_id'] ?? 0);
        $this->fecha = $data['fecha'] ?? null;
        $this->total = (float) ($data['total'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'usuario_id'   => $this->usuario_id,
            'fecha'        => $this->fecha,
            'total'        => $this->total,
        ];
    }
}
