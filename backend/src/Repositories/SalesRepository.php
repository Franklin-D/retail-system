<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;
use Retail\Models\Venta;

class SalesRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                INNER JOIN usuarios u ON u.id = v.usuario_id
                ORDER BY v.fecha DESC, v.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                INNER JOIN usuarios u ON u.id = v.usuario_id
                WHERE v.id = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $venta = $stmt->fetch();
        if (!$venta) {
            return null;
        }

        $det = $this->pdo->prepare(
            "SELECT d.*, p.nombre AS producto
             FROM detalle_venta d
             INNER JOIN productos p ON p.id = d.producto_id
             WHERE d.venta_id = :id"
        );
        $det->execute([':id' => $id]);
        $venta['items'] = $det->fetchAll();
        return $venta;
    }

    /**
     * Ventas del período, opcionalmente solo las que contienen productos de una categoría.
     * subtotal_categoria es la suma de las líneas de esa categoría (o el total de la venta
     * cuando no se filtra por categoría).
     */
    public function findAllEnRango(?string $desde, ?string $hasta, ?int $categoriaId = null): array
    {
        $where = ["v.motivo = 'Venta'"];
        $params = [];

        if ($desde !== null && $desde !== '') {
            $where[] = 'v.fecha >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $where[] = 'v.fecha < :hasta';
            $params[':hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        }

        $subtotalFiltro = '';
        if ($categoriaId !== null) {
            $subtotalFiltro = ' AND p2.categoria_id = :cat_sub';
            $params[':cat_sub'] = $categoriaId;
            $where[] = 'EXISTS (
                SELECT 1
                FROM detalle_venta dv
                INNER JOIN productos p ON p.id = dv.producto_id
                WHERE dv.venta_id = v.id AND p.categoria_id = :cat_exists
            )';
            $params[':cat_exists'] = $categoriaId;
        }

        $sql = "SELECT v.*, c.nombre AS cliente, u.nombre AS vendedor,
                       (SELECT COALESCE(SUM(dv2.cantidad * dv2.precio_unitario), 0)
                        FROM detalle_venta dv2
                        INNER JOIN productos p2 ON p2.id = dv2.producto_id
                        WHERE dv2.venta_id = v.id{$subtotalFiltro}) AS subtotal_categoria
                FROM ventas v
                LEFT JOIN clientes c ON c.id = v.cliente_id
                INNER JOIN usuarios u ON u.id = v.usuario_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY v.fecha DESC, v.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['total'] = (float) $fila['total'];
            $fila['subtotal_categoria'] = (float) $fila['subtotal_categoria'];
        }
        unset($fila);
        return $filas;
    }

    public function totalEnRango(?string $desde, ?string $hasta, ?int $categoriaId = null): float
    {
        $where = ["v.motivo = 'Venta'"];
        $params = [];
        $joinProducto = '';

        if ($desde !== null && $desde !== '') {
            $where[] = 'v.fecha >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $where[] = 'v.fecha < :hasta';
            $params[':hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        }
        if ($categoriaId !== null) {
            $joinProducto = ' INNER JOIN productos p ON p.id = dv.producto_id';
            $where[] = 'p.categoria_id = :categoria';
            $params[':categoria'] = $categoriaId;
        }

        $sql = "SELECT COALESCE(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
                FROM ventas v
                INNER JOIN detalle_venta dv ON dv.venta_id = v.id{$joinProducto}
                WHERE " . implode(' AND ', $where);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float) ($stmt->fetch()['total'] ?? 0);
    }

    public function topProductos(?string $desde, ?string $hasta, ?int $categoriaId = null, int $limite = 5): array
    {
        $where = ["v.motivo = 'Venta'"];
        $params = [];

        if ($desde !== null && $desde !== '') {
            $where[] = 'v.fecha >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $where[] = 'v.fecha < :hasta';
            $params[':hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        }
        if ($categoriaId !== null) {
            $where[] = 'p.categoria_id = :categoria';
            $params[':categoria'] = $categoriaId;
        }

        $limite = max(1, min(50, $limite));
        $sql = "SELECT p.id AS producto_id, p.nombre, p.categoria_id, c.nombre AS categoria,
                       SUM(dv.cantidad) AS cantidad,
                       SUM(dv.cantidad * dv.precio_unitario) AS total
                FROM detalle_venta dv
                INNER JOIN ventas v ON v.id = dv.venta_id
                INNER JOIN productos p ON p.id = dv.producto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY p.id, p.nombre, p.categoria_id, c.nombre
                ORDER BY cantidad DESC, total DESC, p.nombre ASC
                LIMIT {$limite}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['producto_id'] = (int) $fila['producto_id'];
            $fila['categoria_id'] = (int) $fila['categoria_id'];
            $fila['cantidad'] = (int) $fila['cantidad'];
            $fila['total'] = (float) $fila['total'];
        }
        unset($fila);
        return $filas;
    }

    public function create(array $data, array $items): int
    {
        $sql = "INSERT INTO ventas (cliente_id, usuario_id, fecha, motivo, metodo_pago, total)
                VALUES (:cliente_id, :usuario_id, NOW(), :motivo, :metodo_pago, :total)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cliente_id'  => $data['cliente_id'] ?? 1,
            ':usuario_id'  => $data['usuario_id'],
            ':motivo'      => $data['motivo'] ?? 'Venta',
            ':metodo_pago' => $data['metodo_pago'],
            ':total'       => $data['total'],
        ]);
        $ventaId = (int) $this->pdo->lastInsertId();

        $det = $this->pdo->prepare(
            "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario)
             VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario)"
        );
        foreach ($items as $item) {
            $det->execute([
                ':venta_id'         => $ventaId,
                ':producto_id'      => $item['producto_id'],
                ':cantidad'         => $item['cantidad'],
                ':precio_unitario'  => $item['precio_unitario'],
            ]);
        }

        return $ventaId;
    }
}
