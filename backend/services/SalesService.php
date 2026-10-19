<?php
require_once __DIR__ . '/../repositories/SalesRepository.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../repositories/IncomeRepository.php';
require_once __DIR__ . '/InvoiceService.php';

class SalesService
{
    private SalesRepository $ventas;
    private ProductRepository $productos;
    private IncomeRepository $ingresos;
    private InvoiceService $facturas;

    public function __construct()
    {
        $this->ventas = new SalesRepository();
        $this->productos = new ProductRepository();
        $this->ingresos = new IncomeRepository();
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
            foreach ($lineas as $linea) {
                $total += $linea['cantidad'] * $linea['precio_unitario'];
            }

            $ventaId = $this->ventas->create([
                'cliente_id'  => $clienteId ?: 1,
                'usuario_id'  => $usuarioId,
                'metodo_pago' => $metodoPago,
                'total'       => $total,
            ], $lineas);

            $this->productos->descontarStock($lineas);
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
