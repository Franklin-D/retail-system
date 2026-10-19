<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';

class CustomerRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM clientes ORDER BY nombre");
        return array_map(fn($row) => (new Cliente($row))->toArray(), $stmt->fetchAll());
    }
}
