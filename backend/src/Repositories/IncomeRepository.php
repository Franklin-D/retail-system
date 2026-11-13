<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;
use Retail\Models\Ingreso;
use Retail\Models\Venta;

class IncomeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        return $this->findAllEnRango(null, null);
    }

    public function findAllEnRango(?string $desde, ?string $hasta): array
    {
        [$where, $params] = $this->filtroRango($desde, $hasta);
        $sql = "SELECT * FROM ingresos" . $this->whereSql($where) . " ORDER BY fecha DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($row) => (new Ingreso($row))->toArray(), $stmt->fetchAll());
    }

    public function totalEnRango(?string $desde, ?string $hasta): float
    {
        [$where, $params] = $this->filtroRango($desde, $hasta);
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total FROM ingresos" . $this->whereSql($where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float) ($stmt->fetch()['total'] ?? 0);
    }

    public function registrar(float $monto, string $metodoPago, int $usuarioId, ?int $ventaId = null): int
    {
        $sql = "INSERT INTO ingresos (fecha, descripcion, monto, metodo_pago, venta_id, usuario_id)
                VALUES (NOW(), :descripcion, :monto, :metodo_pago, :venta_id, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => "Venta ({$metodoPago})",
            ':monto'       => $monto,
            ':metodo_pago' => $metodoPago,
            ':venta_id'    => $ventaId,
            ':usuario_id'  => $usuarioId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function total(): float
    {
        return $this->totalEnRango(null, null);
    }

    private function filtroRango(?string $desde, ?string $hasta): array
    {
        $where = [];
        $params = [];
        if ($desde !== null && $desde !== '') {
            $where[] = 'fecha >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }
        if ($hasta !== null && $hasta !== '') {
            $where[] = 'fecha < :hasta';
            $params[':hasta'] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        }
        return [$where, $params];
    }

    private function whereSql(array $where): string
    {
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }
}
