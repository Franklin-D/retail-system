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
        $stmt = $this->pdo->query("SELECT * FROM egresos ORDER BY fecha DESC, id DESC");
        return array_map(fn($row) => (new Egreso($row))->toArray(), $stmt->fetchAll());
    }

    /**
     * Egreso extraordinario de caja (RF-2.2). No está asociado a una compra.
     */
    public function registrarManual(array $data, int $usuarioId): int
    {
        $sql = "INSERT INTO egresos
                    (fecha, descripcion, monto, motivo, comprobante, compra_id, usuario_id)
                VALUES
                    (NOW(), :descripcion, :monto, :motivo, :comprobante, NULL, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => $data['descripcion'],
            ':monto'       => $data['monto'],
            ':motivo'      => $data['motivo'] ?? 'Otro',
            ':comprobante' => $data['comprobante'] ?? null,
            ':usuario_id'  => $usuarioId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Egreso generado automáticamente al registrar una compra a proveedor. */
    public function registrarCompra(float $monto, int $usuarioId, int $compraId, string $proveedor, ?string $comprobante = null): int
    {
        $sql = "INSERT INTO egresos
                    (fecha, descripcion, monto, motivo, comprobante, compra_id, usuario_id)
                VALUES
                    (NOW(), :descripcion, :monto, 'Compra', :comprobante, :compra_id, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => "Compra a proveedor: {$proveedor}",
            ':monto'       => $monto,
            ':comprobante' => $comprobante,
            ':compra_id'   => $compraId,
            ':usuario_id'  => $usuarioId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM egresos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? (new Egreso($row))->toArray() : null;
    }

    public function total(): float
    {
        $row = $this->pdo->query("SELECT COALESCE(SUM(monto), 0) AS total FROM egresos")->fetch();
        return (float) ($row['total'] ?? 0);
    }
}
