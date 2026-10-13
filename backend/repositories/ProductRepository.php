<?php
// backend/repositories/ProductRepository.php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';

class ProductoRepository {
    private ?PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function findAll(?string $search = null): array {
        $sql = "SELECT * FROM productos WHERE estado = 1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nombre LIKE :search OR codigo LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(fn($row) => (new Producto($row))->toArray(), $rows);
    }

    public function findById(int $id): ?Producto {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE id = :id AND estado = 1 LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? new Producto($row) : null;
    }

    public function findByCodigo(string $codigo, ?int $excludeId = null): ?Producto {
        $sql = "SELECT * FROM productos WHERE codigo = :codigo AND estado = 1";
        $params = [':codigo' => $codigo];

        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params[':excludeId'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? new Producto($row) : null;
    }

    public function create(Producto $p): int {
        $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio_compra, precio_venta, stock, stock_minimo, categoria_id, estado)
                VALUES (:codigo, :nombre, :descripcion, :precio_compra, :precio_venta, :stock, :stock_minimo, :categoria_id, :estado)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo'        => $p->codigo,
            ':nombre'        => $p->nombre,
            ':descripcion'   => $p->descripcion,
            ':precio_compra' => $p->precio_compra,
            ':precio_venta'  => $p->precio_venta,
            ':stock'         => $p->stock,
            ':stock_minimo'  => $p->stock_minimo,
            ':categoria_id'  => $p->categoria_id,
            ':estado'        => $p->estado
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, Producto $p): bool {
        $sql = "UPDATE productos SET 
                    codigo = :codigo,
                    nombre = :nombre,
                    descripcion = :descripcion,
                    precio_compra = :precio_compra,
                    precio_venta = :precio_venta,
                    stock = :stock,
                    stock_minimo = :stock_minimo,
                    categoria_id = :categoria_id
                WHERE id = :id AND estado = 1";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'            => $id,
            ':codigo'        => $p->codigo,
            ':nombre'        => $p->nombre,
            ':descripcion'   => $p->descripcion,
            ':precio_compra' => $p->precio_compra,
            ':precio_venta'  => $p->precio_venta,
            ':stock'         => $p->stock,
            ':stock_minimo'  => $p->stock_minimo,
            ':categoria_id'  => $p->categoria_id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("UPDATE productos SET estado = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}