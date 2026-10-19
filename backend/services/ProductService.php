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
        $data['estado'] = ProductRepository::PUBLICADO; // el cliente no puede crear productos ocultos por esta vía
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
        $data['estado'] = ProductRepository::PUBLICADO;
        $this->validar($data);
        if ($this->repo->findByCodigo($data['codigo_barra'], $id)) {
            throw new Exception('El código de barras ya está asignado a otro producto', 400);
        }
        $this->repo->update($id, new Producto($data));
        return $this->obtener($id);
    }

    // ------------------------------------------------------------------
    // Pre-carga (borradores): se guardan con estado 'borrador' y no aparecen
    // en Productos, Ventas ni Reportes hasta que se "dan de alta" (publicar).
    // ------------------------------------------------------------------

    public function listarBorradores(?string $search = null): array
    {
        return $this->repo->findAll($search, ProductRepository::BORRADOR);
    }

    public function obtenerBorrador(int $id): array
    {
        $borrador = $this->repo->findById($id, ProductRepository::BORRADOR);
        if (!$borrador) {
            throw new Exception('Borrador no encontrado', 404);
        }
        return $borrador;
    }

    public function crearBorrador(array $data): array
    {
        $data['estado'] = ProductRepository::BORRADOR;
        $this->validarBorrador($data);
        if ($this->repo->findByCodigo($data['codigo_barra'])) {
            throw new Exception('El código de barras ya existe', 400);
        }
        $id = $this->repo->create(new Producto($data));
        return $this->obtenerBorrador($id);
    }

    public function actualizarBorrador(int $id, array $data): array
    {
        $actual = $this->obtenerBorrador($id);
        $data = $this->combinarConActual($actual, $data, ProductRepository::BORRADOR);
        $this->validarBorrador($data);
        if ($this->repo->findByCodigo($data['codigo_barra'], $id)) {
            throw new Exception('El código de barras ya está asignado a otro producto', 400);
        }
        $this->repo->update($id, new Producto($data));
        return $this->obtenerBorrador($id);
    }

    /**
     * "Dar de alta": completa los datos que faltaban y pasa el borrador a 'publicado'.
     * Es el mismo registro (UPDATE), por eso el empleado no vuelve a escribir todo.
     */
    public function publicar(int $id, array $data): array
    {
        $actual = $this->obtenerBorrador($id);
        $data = $this->combinarConActual($actual, $data, ProductRepository::PUBLICADO);
        $this->validar($data, 0.01); // para vender, el precio debe ser mayor a cero
        if ($this->repo->findByCodigo($data['codigo_barra'], $id)) {
            throw new Exception('El código de barras ya está asignado a otro producto', 400);
        }
        $this->repo->update($id, new Producto($data));
        return $this->obtener($id);
    }

    /** Descarta el borrador. Devuelve el nombre de su imagen para que el controlador la borre del disco. */
    public function descartarBorrador(int $id): ?string
    {
        $borrador = $this->obtenerBorrador($id);
        $this->repo->deleteBorrador($id);
        return $borrador['imagen'] ?? null;
    }

    public function eliminar(int $id): bool
    {
        $this->obtener($id);
        return $this->repo->delete($id);
    }

    /** Lo que ya está guardado se conserva; solo se pisa lo que llega en la petición. */
    private function combinarConActual(array $actual, array $data, string $estado): array
    {
        if (empty($data['imagen'])) {
            unset($data['imagen']); // sin foto nueva -> se queda la anterior
        }
        $data = array_merge($actual, $data);
        $data['estado'] = $estado; // siempre lo fija el servidor
        return $data;
    }

    private function validar(array $data, float $precioMinimo = 0): void
    {
        $errors = Validator::validate($data, [
            'nombre'        => 'required',
            'codigo_barra'  => 'required',
            'categoria_id'  => 'required|numeric|min:1',
            'proveedor_id'  => 'required|numeric|min:1',
            'precio'        => "required|numeric|min:{$precioMinimo}",
            'stock'         => 'required|numeric|min:0',
        ]);
        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }
    }

    /** Un borrador solo exige lo imprescindible; precio y stock son opcionales (se vuelven 0). */
    private function validarBorrador(array $data): void
    {
        $errors = Validator::validate($data, [
            'nombre'        => 'required',
            'codigo_barra'  => 'required',
            'categoria_id'  => 'required|numeric|min:1',
            'proveedor_id'  => 'required|numeric|min:1',
            'precio'        => 'numeric|min:0',
            'stock'         => 'numeric|min:0',
        ]);
        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }
    }
}
