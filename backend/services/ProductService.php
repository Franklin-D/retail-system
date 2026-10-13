<?php
// backend/services/ProductoService.php

require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../utils/Validator.php';

class ProductoService {
    private ProductRepository $repo;

    public function __construct() {
        $this->repo = new ProductRepository();
    }

    public function listProductos(?string $search = null): array {
        return $this->repo->findAll($search);
    }

    public function getProducto(int $id): array {
        $producto = $this->repo->findById($id);
        if (!$producto) {
            throw new Exception("Producto no encontrado", 404);
        }
        return $producto->toArray();
    }

    public function createProducto(array $data): array {
        $errors = Validator::validate($data, [
            'codigo'        => 'required',
            'nombre'        => 'required',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock'         => 'required|numeric|min:0'
        ]);

        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }

        if ($this->repo->findByCodigo($data['codigo'])) {
            throw new Exception("El código de producto ya existe", 400);
        }

        $producto = new Producto($data);
        $newId = $this->repo->create($producto);

        return $this->getProducto($newId);
    }

    public function updateProducto(int $id, array $data): array {
        $this->getProducto($id); // Verifica existencia

        $errors = Validator::validate($data, [
            'codigo'        => 'required',
            'nombre'        => 'required',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock'         => 'required|numeric|min:0'
        ]);

        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }

        if ($this->repo->findByCodigo($data['codigo'], $id)) {
            throw new Exception("El código ya está asignado a otro producto", 400);
        }

        $producto = new Producto($data);
        $this->repo->update($id, $producto);

        return $this->getProducto($id);
    }

    public function deleteProducto(int $id): bool {
        $this->getProducto($id); // Verifica existencia
        return $this->repo->delete($id);
    }
}