<?php
// backend/models/Producto.php

class Producto {
    public ?int $id;
    public string $codigo;
    public string $nombre;
    public ?string $descripcion;
    public float $precio_compra;
    public float $precio_venta;
    public int $stock;
    public int $stock_minimo;
    public ?int $categoria_id;
    public int $estado;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->codigo = $data['codigo'] ?? '';
        $this->nombre = $data['nombre'] ?? '';
        $this->descripcion = $data['descripcion'] ?? null;
        $this->precio_compra = isset($data['precio_compra']) ? (float)$data['precio_compra'] : 0.0;
        $this->precio_venta = isset($data['precio_venta']) ? (float)$data['precio_venta'] : 0.0;
        $this->stock = isset($data['stock']) ? (int)$data['stock'] : 0;
        $this->stock_minimo = isset($data['stock_minimo']) ? (int)$data['stock_minimo'] : 5;
        $this->categoria_id = isset($data['categoria_id']) && $data['categoria_id'] !== '' ? (int)$data['categoria_id'] : null;
        $this->estado = isset($data['estado']) ? (int)$data['estado'] : 1;
    }

    public function toArray(): array {
        return [
            'id'            => $this->id,
            'codigo'        => $this->codigo,
            'nombre'        => $this->nombre,
            'descripcion'   => $this->descripcion,
            'precio_compra' => $this->precio_compra,
            'precio_venta'  => $this->precio_venta,
            'stock'         => $this->stock,
            'stock_minimo'  => $this->stock_minimo,
            'categoria_id'  => $this->categoria_id,
            'estado'        => $this->estado
        ];
    }
}