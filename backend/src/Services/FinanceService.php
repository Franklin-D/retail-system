<?php

namespace Retail\Services;

use Exception;
use Retail\Repositories\ExpenseRepository;
use Retail\Repositories\IncomeRepository;
use Retail\Repositories\ProductRepository;
use Retail\Repositories\SalesRepository;
use Retail\Utils\Validator;

class FinanceService
{
    private IncomeRepository $ingresos;
    private ExpenseRepository $egresos;
    private SalesRepository $ventas;
    private ProductRepository $productos;

    private const MOTIVOS_EGRESO = ['Compra', 'Devolucion', 'Producto danado', 'Producto vencido', 'Otro'];

    public function __construct()
    {
        $this->ingresos = new IncomeRepository();
        $this->egresos = new ExpenseRepository();
        $this->ventas = new SalesRepository();
        $this->productos = new ProductRepository();
    }

    public function listarIngresos(): array
    {
        return $this->ingresos->findAll();
    }

    public function listarEgresos(): array
    {
        return $this->egresos->findAll();
    }

    public function movimientos(): array
    {
        return $this->construirMovimientos(
            $this->ventas->findAllEnRango(null, null),
            $this->ingresos->findAllEnRango(null, null),
            $this->egresos->findAllEnRango(null, null)
        );
    }

    /**
     * Datos agregados del dashboard financiero.
     * - Las ventas se filtran por fecha y, opcionalmente, por categoría de producto.
     * - Ingresos y egresos de caja se filtran por fecha; no tienen categoría.
     * - En los movimientos, cada venta reemplaza a su ingreso automático para no duplicar.
     */
    public function dashboard($desde = null, $hasta = null, $categoriaId = null): array
    {
        [$desde, $hasta] = $this->normalizarRango($desde, $hasta);
        $categoriaId = $this->normalizarCategoria($categoriaId);
        $hoy = date('Y-m-d');

        $ingresos = $this->ingresos->findAllEnRango($desde, $hasta);
        $egresos = $this->egresos->findAllEnRango($desde, $hasta);
        $ventas = $this->ventas->findAllEnRango($desde, $hasta, $categoriaId);

        $ingresosPeriodo = $this->ingresos->totalEnRango($desde, $hasta);
        $egresosPeriodo = $this->egresos->totalEnRango($desde, $hasta);

        return [
            'filtros' => [
                'desde'        => $desde,
                'hasta'        => $hasta,
                'categoria_id' => $categoriaId,
            ],
            'resumen' => [
                'ventas_hoy'       => $this->ventas->totalEnRango($hoy, $hoy, $categoriaId),
                'ventas_periodo'   => $this->ventas->totalEnRango($desde, $hasta, $categoriaId),
                'ingresos_periodo' => $ingresosPeriodo,
                'egresos_periodo'  => $egresosPeriodo,
                'balance_periodo'  => $ingresosPeriodo - $egresosPeriodo,
            ],
            'serie'         => $this->construirSerie($ingresos, $egresos),
            'top_productos' => $this->ventas->topProductos($desde, $hasta, $categoriaId, 5),
            'alertas_stock' => $this->productos->findAlertasStock($categoriaId),
            'movimientos'   => $this->construirMovimientos($ventas, $ingresos, $egresos),
        ];
    }

    public function registrarEgreso(array $data, int $usuarioId): array
    {
        $errors = Validator::validate($data, [
            'descripcion' => 'required',
            'monto'       => 'required|numeric|min:0.01',
        ]);
        if (!empty($errors)) {
            throw new Exception(json_encode($errors), 422);
        }

        $descripcion = trim((string) $data['descripcion']);
        if (mb_strlen($descripcion) > 255) {
            throw new Exception('La descripción no puede superar 255 caracteres', 422);
        }

        $monto = (float) $data['monto'];
        if ($monto > 99999999.99) {
            throw new Exception('El monto del egreso es demasiado alto', 422);
        }

        $motivo = trim((string) ($data['motivo'] ?? 'Otro'));
        if (!in_array($motivo, self::MOTIVOS_EGRESO, true)) {
            throw new Exception('Motivo de egreso no válido', 400);
        }

        $fecha = $this->normalizarFecha($data['fecha'] ?? null, 'del egreso');
        $compraId = null;
        if (isset($data['compra_id']) && $data['compra_id'] !== '') {
            if (!is_numeric($data['compra_id']) || (int) $data['compra_id'] < 1) {
                throw new Exception('La compra asociada no es válida', 400);
            }
            $compraId = (int) $data['compra_id'];
            if (!$this->egresos->existeCompra($compraId)) {
                throw new Exception('La compra asociada no existe', 400);
            }
        }

        $id = $this->egresos->registrar(
            $descripcion,
            $monto,
            $motivo,
            $usuarioId,
            $compraId,
            $fecha ? $fecha . ' 00:00:00' : null
        );

        $egreso = $this->egresos->findById($id);
        if (!$egreso) {
            throw new Exception('No se pudo leer el egreso registrado', 500);
        }
        return $egreso;
    }

    private function construirSerie(array $ingresos, array $egresos): array
    {
        $porDia = [];
        $acumular = static function (array $filas, string $clave) use (&$porDia): void {
            foreach ($filas as $fila) {
                $fecha = substr((string) ($fila['fecha'] ?? ''), 0, 10);
                if ($fecha === '') {
                    continue;
                }
                $porDia[$fecha] = $porDia[$fecha] ?? ['ingresos' => 0.0, 'egresos' => 0.0];
                $porDia[$fecha][$clave] += (float) ($fila['monto'] ?? 0);
            }
        };

        $acumular($ingresos, 'ingresos');
        $acumular($egresos, 'egresos');
        ksort($porDia);

        $serie = [];
        foreach ($porDia as $fecha => $montos) {
            $serie[] = [
                'fecha'    => $fecha,
                'ingresos' => round($montos['ingresos'], 2),
                'egresos'  => round($montos['egresos'], 2),
            ];
        }
        return $serie;
    }

    private function construirMovimientos(array $ventas, array $ingresos, array $egresos): array
    {
        $movimientos = [];

        foreach ($ventas as $venta) {
            $cliente = $venta['cliente'] ?: 'Mostrador';
            $movimientos[] = [
                'id'          => 'venta-' . $venta['id'],
                'fecha'       => $venta['fecha'],
                'descripcion' => "Venta #{$venta['id']} · {$cliente}",
                'tipo'        => 'Venta',
                'monto'       => (float) $venta['subtotal_categoria'],
                'metodo_pago' => $venta['metodo_pago'],
            ];
        }

        foreach ($ingresos as $ingreso) {
            // Los ingresos generados por una venta se representan con la fila "Venta".
            if (!empty($ingreso['venta_id'])) {
                continue;
            }
            $movimientos[] = [
                'id'          => 'ingreso-' . $ingreso['id'],
                'fecha'       => $ingreso['fecha'],
                'descripcion' => $ingreso['descripcion'],
                'tipo'        => 'Ingreso',
                'monto'       => (float) $ingreso['monto'],
                'metodo_pago' => $ingreso['metodo_pago'],
            ];
        }

        foreach ($egresos as $egreso) {
            $movimientos[] = [
                'id'          => 'egreso-' . $egreso['id'],
                'fecha'       => $egreso['fecha'],
                'descripcion' => $egreso['descripcion'],
                'tipo'        => 'Egreso',
                'monto'       => (float) $egreso['monto'],
                'motivo'      => $egreso['motivo'],
            ];
        }

        usort($movimientos, fn($a, $b) => strcmp((string) $b['fecha'], (string) $a['fecha']));
        return $movimientos;
    }

    private function normalizarRango($desde, $hasta): array
    {
        $desde = $this->normalizarFecha($desde, 'de inicio');
        $hasta = $this->normalizarFecha($hasta, 'de fin');
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha fin', 400);
        }
        return [$desde, $hasta];
    }

    private function normalizarFecha($valor, string $campo): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_string($valor)) {
            throw new Exception("La fecha {$campo} no es válida", 400);
        }
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            throw new Exception("La fecha {$campo} debe tener formato YYYY-MM-DD", 400);
        }
        [$anio, $mes, $dia] = array_map('intval', explode('-', $valor));
        if (!checkdate($mes, $dia, $anio)) {
            throw new Exception("La fecha {$campo} no es válida", 400);
        }
        return $valor;
    }

    private function normalizarCategoria($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (!is_numeric($valor) || (int) $valor < 1) {
            throw new Exception('La categoría no es válida', 400);
        }
        return (int) $valor;
    }
}
