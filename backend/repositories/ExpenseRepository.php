<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Egreso.php';

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

    public function total(): float
    {
        $row = $this->pdo->query("SELECT COALESCE(SUM(monto), 0) AS total FROM egresos")->fetch();
        return (float) ($row['total'] ?? 0);
    }
}
