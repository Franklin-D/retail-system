<?php

namespace Retail\Services;

use Exception;
use Throwable;
use Retail\Models\Venta;
use Retail\Repositories\IncomeRepository;
use Retail\Repositories\InventoryMovementRepository;
use Retail\Repositories\ProductRepository;
use Retail\Repositories\SalesRepository;
use Retail\Utils\ExpiryPolicy;

class SalesService
{
    private SalesRepository $ventas;
    private ProductRepository $productos;
    private IncomeRepository $ingresos;
    private InventoryMovementRepository $movimientos;
    private InvoiceService $facturas;

    public function __construct()
    {
        $this->ventas = new SalesRepository();
        $this->productos = new ProductRepository();
        $this->ingresos = new IncomeRepository();
        $this->movimientos = new InventoryMovementRepository();
        $this->facturas = new InvoiceService();
    }

    public function listar(): array
    {
        return $this->ventas->findAll();
    }

    public function obtener(int $id): array
    {
        $venta = $this->ventas->findById($id);
        if (!$venta) {
            throw new Exception('Venta no encontrada', 404);
        }
        return $venta;
    }

    public function checkout(array $items, string $metodoPago, int $usuarioId, ?int $clienteId = null): array
    {
        $metodos = ['Efectivo', 'Tarjeta', 'Yape', 'Plin'];
        if (!in_array($metodoPago, $metodos, true)) {
            throw new Exception('Método de pago no válido', 400);
        }
        if (empty($items) || !is_array($items)) {
            throw new Exception('El carrito está vacío', 400);
        }

        $pdo = $this->productos->getPdo();
        $pdo->beginTransaction();
        try {
            $lineas = $this->productos->verificarDisponibilidad($items);
            $total = 0;
            // El descuento por vencimiento se calcula en el servidor, no en el cliente.
            foreach ($lineas as &$linea) {
                $linea['precio_base'] = $linea['precio_unitario'];
                $linea['descuento_porcentaje'] = ExpiryPolicy::descuentoPorcentaje($linea['fecha_vencimiento']);
                $linea['precio_unitario'] = ExpiryPolicy::precioConDescuento(
                    $linea['precio_unitario'],
                    $linea['fecha_vencimiento']
                );
                $total += $linea['cantidad'] * $linea['precio_unitario'];
            }
            unset($linea);

            $ventaId = $this->ventas->create([
                'cliente_id'  => $clienteId ?: 1,
                'usuario_id'  => $usuarioId,
                'metodo_pago' => $metodoPago,
                'total'       => $total,
            ], $lineas);

            $this->productos->descontarStock($lineas);
            $stockActual = [];
            foreach ($lineas as $linea) {
                $productoId = (int) $linea['producto_id'];
                if (!isset($stockActual[$productoId])) {
                    $stockActual[$productoId] = (int) $linea['stock'];
                }
                $anterior = $stockActual[$productoId];
                $nuevo = $anterior - (int) $linea['cantidad'];
                $stockActual[$productoId] = $nuevo;

                $this->movimientos->registrar([
                    'producto_id'    => $productoId,
                    'usuario_id'     => $usuarioId,
                    'tipo'           => 'salida',
                    'cantidad'       => -1 * (int) $linea['cantidad'],
                    'stock_anterior' => $anterior,
                    'stock_nuevo'    => $nuevo,
                    'origen'         => 'venta',
                    'referencia_id'  => $ventaId,
                    'motivo'         => "Venta #{$ventaId} - {$linea['nombre']}",
                ]);
            }
            $this->ingresos->registrar($total, $metodoPago, $usuarioId, $ventaId);

            $venta = $this->ventas->findById($ventaId);
            $factura = $this->facturas->generar($venta);

            $pdo->commit();
            return ['venta' => $venta, 'factura' => $factura];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
