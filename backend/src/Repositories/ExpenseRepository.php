<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;
use Retail\Models\Egreso;

class ExpenseRepository
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
        $sql = "SELECT * FROM egresos" . $this->whereSql($where) . " ORDER BY fecha DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($row) => (new Egreso($row))->toArray(), $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM egresos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? (new Egreso($row))->toArray() : null;
    }

    public function totalEnRango(?string $desde, ?string $hasta): float
    {
        [$where, $params] = $this->filtroRango($desde, $hasta);
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total FROM egresos" . $this->whereSql($where);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (float) ($stmt->fetch()['total'] ?? 0);
    }

    public function total(): float
    {
        return $this->totalEnRango(null, null);
    }

    public function existeCompra(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM compras WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public function registrar(
        string $descripcion,
        float $monto,
        string $motivo,
        int $usuarioId,
        ?int $compraId = null,
        ?string $fecha = null
    ): int {
        $sql = "INSERT INTO egresos (fecha, descripcion, monto, motivo, compra_id, usuario_id)
                VALUES (:fecha, :descripcion, :monto, :motivo, :compra_id, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':fecha'       => $fecha ?: date('Y-m-d H:i:s'),
            ':descripcion' => $descripcion,
            ':monto'       => $monto,
            ':motivo'      => $motivo,
            ':compra_id'   => $compraId,
            ':usuario_id'  => $usuarioId,
        ]);
        return (int) $this->pdo->lastInsertId();
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
