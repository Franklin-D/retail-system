<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Proveedor.php';

class SupplierRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM proveedores ORDER BY nombre");
        return array_map(fn($row) => (new Proveedor($row))->toArray(), $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM proveedores WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? (new Proveedor($row))->toArray() : null;
    }
}
