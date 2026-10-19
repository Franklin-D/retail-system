<?php
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../utils/Validator.php';

class ProductService
{
    private ProductRepository $repo;

    public function __construct()
    {
        $this->repo = new ProductRepository();
    }

    public function listar(?string $search = null): array
    {
        return $this->repo->findAll($search);
    }

    public function obtener(int $id): array
    {
        $producto = $this->repo->findById($id);
        if (!$producto) {
            throw new Exception('Producto no encontrado', 404);
        }
        return $producto;
    }

    public function crear(array $data): array
    {
        $this->validar($data);
        if ($this->repo->findByCodigo($data['codigo_barra'])) {
            throw new Exception('El código de barras ya existe', 400);
        }
        $id = $this->repo->create(new Producto($data));
        return $this->obtener($id);
    }

    public function actualizar(int $id, array $data): array
    {
        $actual = $this->obtener($id);
        if (empty($data['imagen'])) {
            $data['imagen'] = $actual['imagen'];
        }
        $this->validar($data);
        if ($this->repo->findByCodigo($data['codigo_barra'], $id)) {
            throw new Exception('El código de barras ya está asignado a otro producto', 400);
        }
        $this->repo->update($id, new Producto($data));
        return $this->obtener($id);
    }

    public function eliminar(int $id): bool
    {
        $this->obtener($id);
        return $this->repo->delete($id);
    }

    private function validar(array $data): void
    {
        $errors = Validator::validate($data, [
            'nombre'        => 'required',
            'codigo_barra'  => 'required',
            'categoria_id'  => 'required|numeric|min:1',
            'proveedor_id'  => 'required|numeric|min:1',
            'precio'        => 'required|numeric|min:0',
            'stock'         => 'required|numeric|min:0',
        ]);
        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }
    }
}
