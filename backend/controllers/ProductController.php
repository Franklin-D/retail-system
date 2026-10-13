<?php
// backend/controllers/ProductoController.php

require_once __DIR__ . '/../services/ProductService.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/auth.middleware.php';

class ProductoController {
    private ProductService $service;

    public function __construct() {
        $this->service = new ProductService();
    }

    public function index(): void {
        requireAuth();
        $search = $_GET['search'] ?? null;
        $productos = $this->service->listProductos($search);
        Response::success($productos, 'Listado de productos obtenido');
    }

    public function show(int $id): void {
        requireAuth();
        try {
            $producto = $this->service->getProducto($id);
            Response::success($producto, 'Producto obtenido');
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function store(): void {
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $nuevoProducto = $this->service->createProducto($data);
            Response::success($nuevoProducto, 'Producto creado exitosamente', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function update(int $id): void {
        requireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $productoActualizado = $this->service->updateProducto($id, $data);
            Response::success($productoActualizado, 'Producto actualizado exitosamente');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function destroy(int $id): void {
        requireAuth();
        try {
            $this->service->deleteProducto($id);
            Response::success(null, 'Producto eliminado exitosamente');
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    private function handleException(Exception $e): void {
        if ($e->getCode() === 422) {
            Response::json([
                'success' => false,
                'error'   => 'Errores de validación',
                'details' => json_decode($e->getMessage(), true)
            ], 422);
        } else {
            Response::error($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}