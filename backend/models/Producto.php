<?php
class Producto
{
    public ?int $id;
    public string $nombre;
    public string $codigo_barra;
    public ?string $descripcion;
    public int $categoria_id;
    public ?string $marca;
    public int $proveedor_id;
    public ?string $pasillo;
    public int $stock;
    public int $stock_minimo;
    public float $precio;
    public ?string $fecha_vencimiento;
    public ?string $imagen;
    public int $activo;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->nombre = $data['nombre'] ?? '';
        $this->codigo_barra = $data['codigo_barra'] ?? ($data['codigo'] ?? '');
        $this->descripcion = $data['descripcion'] ?? null;
        $this->categoria_id = (int) ($data['categoria_id'] ?? 0);
        $this->marca = $data['marca'] ?? null;
        $this->proveedor_id = (int) ($data['proveedor_id'] ?? 0);
        $this->pasillo = $data['pasillo'] ?? null;
        $this->stock = (int) ($data['stock'] ?? 0);
        $this->stock_minimo = (int) ($data['stock_minimo'] ?? 5);
        $this->precio = (float) ($data['precio'] ?? ($data['precio_venta'] ?? 0));
        $fecha = $data['fecha_vencimiento'] ?? null;
        $this->fecha_vencimiento = ($fecha === '' || $fecha === null) ? null : $fecha;
        $this->imagen = $data['imagen'] ?? null;
        $this->activo = isset($data['activo']) ? (int) $data['activo'] : 1;
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'nombre'            => $this->nombre,
            'codigo_barra'      => $this->codigo_barra,
            'descripcion'       => $this->descripcion,
            'categoria_id'      => $this->categoria_id,
            'marca'             => $this->marca,
            'proveedor_id'      => $this->proveedor_id,
            'pasillo'           => $this->pasillo,
            'stock'             => $this->stock,
            'stock_minimo'      => $this->stock_minimo,
            'precio'            => $this->precio,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'imagen'            => $this->imagen,
            'activo'            => $this->activo,
        ];
    }
}
