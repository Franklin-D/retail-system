<?php
require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../repositories/IncomeRepository.php';
require_once __DIR__ . '/../repositories/ExpenseRepository.php';
require_once __DIR__ . '/../repositories/SalesRepository.php';

class ReportService
{
    private ProductRepository $productos;
    private IncomeRepository $ingresos;
    private ExpenseRepository $egresos;
    private SalesRepository $ventas;

    public function __construct()
    {
        $this->productos = new ProductRepository();
        $this->ingresos = new IncomeRepository();
        $this->egresos = new ExpenseRepository();
        $this->ventas = new SalesRepository();
    }

    public function catalogo(): array
    {
        return [
            [
                'clave'       => 'summary',
                'nombre'      => 'Resumen general',
                'descripcion' => 'Totales de productos, stock, valor de inventario y ganancia',
            ],
            [
                'clave'       => 'inventory',
                'nombre'      => 'Inventario',
                'descripcion' => 'Stock actual, alertas de mínimo y vencimiento',
            ],
            [
                'clave'       => 'profits',
                'nombre'      => 'Ganancias',
                'descripcion' => 'Ingresos menos egresos y listado de ventas',
            ],
        ];
    }

    public function resumen(): array
    {
        $inventario = $this->productos->resumenInventario();
        $totalIngresos = $this->ingresos->total();
        $totalEgresos = $this->egresos->total();
        return [
            'total_productos'  => (int) $inventario['total_productos'],
            'total_stock'      => (int) $inventario['total_stock'],
            'valor_inventario' => (float) $inventario['valor_inventario'],
            'total_ingresos'   => $totalIngresos,
            'total_egresos'    => $totalEgresos,
            'ganancia'         => $totalIngresos - $totalEgresos,
        ];
    }

    public function inventario(): array
    {
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime('+30 days'));
        $items = $this->productos->findAll();
        foreach ($items as &$item) {
            $item['alerta_stock'] = (int) $item['stock'] <= (int) $item['stock_minimo'];
            $venc = $item['fecha_vencimiento'] ?? null;
            $item['alerta_vencimiento'] = $venc && $venc <= $limite;
            $item['vencido'] = $venc && $venc < $hoy;
        }
        unset($item);
        return $items;
    }

    public function ganancias(): array
    {
        $resumen = $this->resumen();
        $resumen['ventas'] = $this->ventas->findAll();
        return $resumen;
    }
}
