<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Compra.php';

class PurchaseRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, p.nombre AS proveedor
                FROM compras c
                INNER JOIN proveedores p ON p.id = c.proveedor_id
                ORDER BY c.fecha DESC, c.id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }
}
