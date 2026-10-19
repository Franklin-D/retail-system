<?php
require_once __DIR__ . '/../services/ProductService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';

class ProductController
{
    private ProductService $service;

    public function __construct()
    {
        $this->service = new ProductService();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? null;
        Response::success($this->service->listar($search), 'Listado de productos obtenido');
    }

    public function show(int $id): void
    {
        try {
            Response::success($this->service->obtener($id), 'Producto obtenido');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->normalizar(Request::input());
            $imagen = $this->guardarImagen();
            if ($imagen) {
                $data['imagen'] = $imagen;
            }
            $producto = $this->service->crear($data);
            Response::success($producto, 'Producto creado exitosamente', 201);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function update(int $id): void
    {
        try {
            $data = $this->normalizar(Request::input());
            $imagen = $this->guardarImagen();
            if ($imagen) {
                $data['imagen'] = $imagen;
            }
            $producto = $this->service->actualizar($id, $data);
            Response::success($producto, 'Producto actualizado exitosamente');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function destroy(int $id): void
    {
        try {
            $this->service->eliminar($id);
            Response::success(null, 'Producto eliminado exitosamente');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    private function normalizar(array $data): array
    {
        if (empty($data['codigo_barra']) && !empty($data['codigo'])) {
            $data['codigo_barra'] = $data['codigo'];
        }
        if (!isset($data['precio']) && isset($data['precio_venta'])) {
            $data['precio'] = $data['precio_venta'];
        }
        return $data;
    }

    private function guardarImagen(): ?string
    {
        if (empty($_FILES['imagen']) || ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($_FILES['imagen']['size'] ?? 0) <= 0) {
            return null;
        }
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No se pudo subir la imagen', 400);
        }
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $permitidas, true)) {
            throw new Exception('Formato de imagen no permitido', 400);
        }
        $dir = __DIR__ . '/../public/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception('No se pudo crear la carpeta de imágenes', 500);
        }
        $nombre = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . '/' . $nombre)) {
            throw new Exception('No se pudo guardar la imagen', 500);
        }
        return $nombre;
    }
}
