<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;
use Retail\Models\MovimientoInventario;

class InventoryMovementRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function registrar(array $data): int
    {
        $sql = "INSERT INTO movimientos_inventario
                    (producto_id, usuario_id, fecha, tipo, cantidad, stock_anterior,
                     stock_nuevo, origen, referencia_id, motivo)
                VALUES
                    (:producto_id, :usuario_id, NOW(), :tipo, :cantidad, :stock_anterior,
                     :stock_nuevo, :origen, :referencia_id, :motivo)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':producto_id'    => (int) $data['producto_id'],
            ':usuario_id'     => (int) $data['usuario_id'],
            ':tipo'           => $data['tipo'],
            ':cantidad'       => (int) $data['cantidad'],
            ':stock_anterior' => (int) $data['stock_anterior'],
            ':stock_nuevo'    => (int) $data['stock_nuevo'],
            ':origen'         => $data['origen'] ?? 'ajuste',
            ':referencia_id'  => $data['referencia_id'] ?? null,
            ':motivo'         => $data['motivo'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findAll(array $filtros = []): array
    {
        $sql = "SELECT m.*, p.nombre AS producto, p.codigo_barra, u.nombre AS usuario
                FROM movimientos_inventario m
                INNER JOIN productos p ON p.id = m.producto_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $sql .= " AND m.producto_id = :producto_id";
            $params[':producto_id'] = (int) $filtros['producto_id'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['origen'])) {
            $sql .= " AND m.origen = :origen";
            $params[':origen'] = $filtros['origen'];
        }
        if (!empty($filtros['desde'])) {
            $sql .= " AND m.fecha >= :desde";
            $params[':desde'] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $sql .= " AND m.fecha <= :hasta";
            $params[':hasta'] = $filtros['hasta'] . ' 23:59:59';
        }

        $limite = min(2000, max(1, (int) ($filtros['limite'] ?? 500)));
        $sql .= " ORDER BY m.fecha DESC, m.id DESC LIMIT {$limite}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($row) => array_merge(
            (new MovimientoInventario($row))->toArray(),
            ['producto' => $row['producto'], 'codigo_barra' => $row['codigo_barra'], 'usuario' => $row['usuario']]
        ), $stmt->fetchAll());
    }
}
