<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;

class PurchaseRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, p.nombre AS proveedor, u.nombre AS usuario,
                       (SELECT COUNT(*) FROM detalle_compra d WHERE d.compra_id = c.id) AS total_items
                FROM compras c
                INNER JOIN proveedores p ON p.id = c.proveedor_id
                INNER JOIN usuarios u ON u.id = c.usuario_id
                ORDER BY c.fecha DESC, c.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*, p.nombre AS proveedor, u.nombre AS usuario
                FROM compras c
                INNER JOIN proveedores p ON p.id = c.proveedor_id
                INNER JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.id = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $compra = $stmt->fetch();
        if (!$compra) {
            return null;
        }

        $detalle = $this->pdo->prepare(
            "SELECT d.*, p.nombre AS producto, p.codigo_barra,
                    (d.cantidad * d.precio_unitario) AS subtotal
             FROM detalle_compra d
             INNER JOIN productos p ON p.id = d.producto_id
             WHERE d.compra_id = :id
             ORDER BY d.id ASC"
        );
        $detalle->execute([':id' => $id]);
        $compra['items'] = $detalle->fetchAll();
        return $compra;
    }

    public function create(array $data, array $items): int
    {
        $sql = "INSERT INTO compras (proveedor_id, usuario_id, fecha, total)
                VALUES (:proveedor_id, :usuario_id, :fecha, :total)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':proveedor_id' => (int) $data['proveedor_id'],
            ':usuario_id'   => (int) $data['usuario_id'],
            ':fecha'        => $data['fecha'] ?? date('Y-m-d H:i:s'),
            ':total'        => $data['total'],
        ]);
        $compraId = (int) $this->pdo->lastInsertId();

        $detalle = $this->pdo->prepare(
            "INSERT INTO detalle_compra (compra_id, producto_id, cantidad, precio_unitario)
             VALUES (:compra_id, :producto_id, :cantidad, :precio_unitario)"
        );
        foreach ($items as $item) {
            $detalle->execute([
                ':compra_id'       => $compraId,
                ':producto_id'     => (int) $item['producto_id'],
                ':cantidad'        => (int) $item['cantidad'],
                ':precio_unitario' => $item['precio_unitario'],
            ]);
        }

        return $compraId;
    }

    public function nombreProveedor(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT nombre FROM proveedores WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $nombre = $stmt->fetchColumn();
        return $nombre !== false ? (string) $nombre : null;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
