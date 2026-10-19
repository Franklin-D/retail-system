<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Ingreso.php';

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

    public function total(): float
    {
        $row = $this->pdo->query("SELECT COALESCE(SUM(monto), 0) AS total FROM ingresos")->fetch();
        return (float) ($row['total'] ?? 0);
    }
}
