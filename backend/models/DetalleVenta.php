<?php
class DetalleVenta
{
    public ?int $id;
    public int $venta_id;
    public int $producto_id;
    public int $cantidad;
    public float $precio_unitario;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->venta_id = (int) ($data['venta_id'] ?? 0);
        $this->producto_id = (int) ($data['producto_id'] ?? 0);
        $this->cantidad = (int) ($data['cantidad'] ?? 0);
        $this->precio_unitario = (float) ($data['precio_unitario'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'venta_id'        => $this->venta_id,
            'producto_id'     => $this->producto_id,
            'cantidad'        => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
        ];
    }
}
