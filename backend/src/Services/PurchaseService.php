<?php

namespace Retail\Services;

use Exception;
use Throwable;
use Retail\Repositories\ExpenseRepository;
use Retail\Repositories\InventoryMovementRepository;
use Retail\Repositories\ProductRepository;
use Retail\Repositories\PurchaseRepository;

class PurchaseService
{
    private PurchaseRepository $repo;
    private ProductRepository $productos;
    private ExpenseRepository $egresos;
    private InventoryMovementRepository $movimientos;

    public function __construct()
    {
        $this->repo = new PurchaseRepository();
        $this->productos = new ProductRepository();
        $this->egresos = new ExpenseRepository();
        $this->movimientos = new InventoryMovementRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }

    public function obtener(int $id): array
    {
        $compra = $this->repo->findById($id);
        if (!$compra) {
            throw new Exception('Compra no encontrada', 404);
        }
        return $compra;
    }

    /**
     * RF-2.4: registra la compra, incrementa el stock de cada producto,
     * deja el movimiento en el Kardex y genera el egreso de caja asociado.
     */
    public function registrar(array $data, int $usuarioId): array
    {
        $proveedorId = (int) ($data['proveedor_id'] ?? 0);
        if ($proveedorId <= 0) {
            throw new Exception('Debe seleccionar un proveedor', 400);
        }

        $items = $data['items'] ?? $data['detalles'] ?? [];
        if (!is_array($items) || empty($items)) {
            throw new Exception('La compra debe tener al menos un producto', 400);
        }

        $pdo = $this->repo->getPdo();
        $pdo->beginTransaction();
        try {
            $lineas = [];
            $total = 0.0;

            foreach ($items as $item) {
                $productoId = (int) ($item['producto_id'] ?? $item['productoId'] ?? 0);
                $cantidad = (int) ($item['cantidad'] ?? 0);
                if ($productoId <= 0) {
                    throw new Exception('Cada línea debe tener un producto válido', 400);
                }
                if ($cantidad <= 0) {
                    throw new Exception('La cantidad de cada producto debe ser mayor a cero', 400);
                }

                $producto = $this->productos->findParaCompra($productoId);
                if (!$producto) {
                    throw new Exception("Producto no encontrado (id {$productoId})", 404);
                }

                if (array_key_exists('precio_unitario', $item)) {
                    $precio = (float) $item['precio_unitario'];
                } elseif (array_key_exists('precio', $item)) {
                    $precio = (float) $item['precio'];
                } else {
                    $precio = (float) $producto['precio'];
                }
                if ($precio < 0) {
                    throw new Exception('El precio unitario no puede ser negativo', 400);
                }

                $lineas[] = [
                    'producto_id'     => $productoId,
                    'nombre'          => $producto['nombre'],
                    'cantidad'        => $cantidad,
                    'precio_unitario' => round($precio, 2),
                ];
                $total += $cantidad * $precio;
            }

            $total = round($total, 2);
            $fecha = $data['fecha'] ?? null;
            if (is_string($fecha) && trim($fecha) === '') {
                $fecha = null;
            }
            $compraId = $this->repo->create([
                'proveedor_id' => $proveedorId,
                'usuario_id'   => $usuarioId,
                'fecha'        => $fecha,
                'total'        => $total,
            ], $lineas);

            foreach ($lineas as $linea) {
                $stock = $this->productos->incrementarStock($linea['producto_id'], $linea['cantidad']);
                $this->movimientos->registrar([
                    'producto_id'    => $linea['producto_id'],
                    'usuario_id'     => $usuarioId,
                    'tipo'           => 'entrada',
                    'cantidad'       => $linea['cantidad'],
                    'stock_anterior' => $stock['stock_anterior'],
                    'stock_nuevo'    => $stock['stock_nuevo'],
                    'origen'         => 'compra',
                    'referencia_id'  => $compraId,
                    'motivo'         => "Compra #{$compraId} - {$linea['nombre']}",
                ]);
            }

            $proveedor = $this->repo->nombreProveedor($proveedorId) ?? 'Proveedor';
            $comprobante = $data['comprobante'] ?? $data['documento'] ?? null;
            $egresoId = $this->egresos->registrarCompra(
                $total,
                $usuarioId,
                $compraId,
                $proveedor,
                $comprobante !== null ? trim((string) $comprobante) : null
            );

            $compra = $this->repo->findById($compraId);
            $pdo->commit();

            return [
                'compra'    => $compra,
                'egreso_id' => $egresoId,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
