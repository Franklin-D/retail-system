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
        $stmt = $this->pdo->query("SELECT * FROM ingresos ORDER BY fecha DESC, id DESC");
        return array_map(fn($row) => (new Ingreso($row))->toArray(), $stmt->fetchAll());
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

    /**
     * Ingreso extraordinario de caja (RF-2.1). No está asociado a una venta.
     */
    public function registrarManual(array $data, int $usuarioId): int
    {
        $sql = "INSERT INTO ingresos
                    (fecha, descripcion, motivo, comprobante, monto, metodo_pago, venta_id, usuario_id)
                VALUES
                    (NOW(), :descripcion, :motivo, :comprobante, :monto, :metodo_pago, NULL, :usuario_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':descripcion' => $data['descripcion'],
            ':motivo'      => $data['motivo'] ?? null,
            ':comprobante' => $data['comprobante'] ?? null,
            ':monto'       => $data['monto'],
            ':metodo_pago' => $data['metodo_pago'] ?? 'Efectivo',
            ':usuario_id'  => $usuarioId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ingresos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? (new Ingreso($row))->toArray() : null;
    }

    public function total(): float
    {
        $row = $this->pdo->query("SELECT COALESCE(SUM(monto), 0) AS total FROM ingresos")->fetch();
        return (float) ($row['total'] ?? 0);
    }
}
