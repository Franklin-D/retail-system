<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Venta.php';

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
