<?php

namespace Retail\Repositories;

use PDO;
use Retail\Config\Database;
use Retail\Models\Cliente;

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
