<?php
require_once __DIR__ . '/../services/ProductService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Request.php';
require_once __DIR__ . '/../middleware/auth.middleware.php';

class ProductController
{
    /** Roles que pueden dar de alta o descartar borradores. Cualquier empleado logueado puede pre-cargar. */
    private const ROLES_ALTA = ['administrador', 'repositor'];

    private const MAX_IMAGEN_BYTES = 5 * 1024 * 1024; // 5 MB

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

    // ------------------------------------------------------------------
    // Pre-carga (borradores)
    // ------------------------------------------------------------------

    public function drafts(): void
    {
        $search = $_GET['search'] ?? null;
        Response::success($this->service->listarBorradores($search), 'Listado de borradores obtenido');
    }

    public function showDraft(int $id): void
    {
        try {
            Response::success($this->service->obtenerBorrador($id), 'Borrador obtenido');
        } catch (Throwable $e) {
            Response::fromException($e);
        }
    }

    public function storeDraft(): void
    {
        $imagenNueva = null;
        try {
            $data = $this->normalizar(Request::input());
            $imagenNueva = $this->guardarImagen();
            if ($imagenNueva) {
                $data['imagen'] = $imagenNueva;
            }
            $borrador = $this->service->crearBorrador($data);
        } catch (Throwable $e) {
            $this->borrarImagen($imagenNueva); // no dejar fotos huérfanas si algo falló
            Response::fromException($e);
        }
        Response::success($borrador, 'Borrador guardado. Aún no es visible en la tienda.', 201);
    }

    public function updateDraft(int $id): void
    {
        $imagenNueva = null;
        try {
            $imagenAnterior = $this->service->obtenerBorrador($id)['imagen'] ?? null;
            $data = $this->normalizar(Request::input());
            $imagenNueva = $this->guardarImagen();
            if ($imagenNueva) {
                $data['imagen'] = $imagenNueva;
            }
            $borrador = $this->service->actualizarBorrador($id, $data);
            if ($imagenNueva) {
                $this->borrarImagen($imagenAnterior);
            }
        } catch (Throwable $e) {
            $this->borrarImagen($imagenNueva);
            Response::fromException($e);
        }
        Response::success($borrador, 'Borrador actualizado');
    }

    public function publishDraft(int $id): void
    {
        requireRole(self::ROLES_ALTA);
        $imagenNueva = null;
        try {
            $imagenAnterior = $this->service->obtenerBorrador($id)['imagen'] ?? null;
            $data = $this->normalizar(Request::input());
            $imagenNueva = $this->guardarImagen();
            if ($imagenNueva) {
                $data['imagen'] = $imagenNueva;
            }
            $producto = $this->service->publicar($id, $data);
            if ($imagenNueva) {
                $this->borrarImagen($imagenAnterior);
            }
        } catch (Throwable $e) {
            $this->borrarImagen($imagenNueva);
            Response::fromException($e);
        }
        Response::success($producto, 'Producto dado de alta. Ya es visible en Productos y Ventas.');
    }

    public function destroyDraft(int $id): void
    {
        requireRole(self::ROLES_ALTA);
        try {
            $imagen = $this->service->descartarBorrador($id);
            $this->borrarImagen($imagen);
        } catch (Throwable $e) {
            Response::fromException($e);
        }
        Response::success(null, 'Borrador descartado');
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

    /**
     * Guarda la foto subida en backend/public/uploads/ con nombre aleatorio y devuelve ese nombre
     * (es lo que se guarda en productos.imagen). Devuelve null si no se envió archivo.
     * El tipo se valida por el CONTENIDO real del archivo, no solo por su extensión.
     */
    private function guardarImagen(): ?string
    {
        if (empty($_FILES['imagen']) || ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $error = $_FILES['imagen']['error'];
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('La imagen supera el tamaño que permite el servidor (revise upload_max_filesize en php.ini)', 400);
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new Exception('No se pudo subir la imagen', 400);
        }
        $tamano = (int) ($_FILES['imagen']['size'] ?? 0);
        if ($tamano <= 0) {
            return null;
        }
        if ($tamano > self::MAX_IMAGEN_BYTES) {
            throw new Exception('La imagen supera el tamaño máximo permitido (5 MB)', 400);
        }

        $tmp = $_FILES['imagen']['tmp_name'];
        $info = @getimagesize($tmp);
        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extensiones[$info['mime'] ?? ''] ?? null;
        if ($ext === null) {
            throw new Exception('Formato de imagen no permitido (use JPG, PNG, GIF o WEBP)', 400);
        }

        $dir = __DIR__ . '/../public/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new Exception('No se pudo crear la carpeta de imágenes', 500);
        }
        $nombre = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . '/' . $nombre)) {
            throw new Exception('No se pudo guardar la imagen', 500);
        }
        return $nombre;
    }

    /** Borra del disco solo fotos subidas por este formulario (prod_xxx), nunca las del seed (Pelota.jpg, etc.). */
    private function borrarImagen(?string $nombre): void
    {
        if (!$nombre || !preg_match('/^prod_[a-f0-9]{16}\.(jpg|png|gif|webp)$/', $nombre)) {
            return;
        }
        $ruta = __DIR__ . '/../public/uploads/' . $nombre;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
