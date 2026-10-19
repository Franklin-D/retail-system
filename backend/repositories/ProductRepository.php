<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Producto.php';

class ProductRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(?string $search = null): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE p.activo = 1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (p.nombre LIKE :search OR p.codigo_barra LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY p.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN proveedores pr ON pr.id = p.proveedor_id
                WHERE p.id = :id AND p.activo = 1
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCodigo(string $codigo, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM productos WHERE codigo_barra = :codigo AND activo = 1";
        $params = [':codigo' => $codigo];
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(Producto $p): int
    {
        $sql = "INSERT INTO productos
                    (nombre, codigo_barra, descripcion, categoria_id, marca, proveedor_id,
                     pasillo, stock, stock_minimo, precio, fecha_vencimiento, imagen, activo)
                VALUES
                    (:nombre, :codigo_barra, :descripcion, :categoria_id, :marca, :proveedor_id,
                     :pasillo, :stock, :stock_minimo, :precio, :fecha_vencimiento, :imagen, :activo)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params($p));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, Producto $p): bool
    {
        $sql = "UPDATE productos SET
                    nombre = :nombre,
                    codigo_barra = :codigo_barra,
                    descripcion = :descripcion,
                    categoria_id = :categoria_id,
                    marca = :marca,
                    proveedor_id = :proveedor_id,
                    pasillo = :pasillo,
                    stock = :stock,
                    stock_minimo = :stock_minimo,
                    precio = :precio,
                    fecha_vencimiento = :fecha_vencimiento,
                    imagen = :imagen
                WHERE id = :id AND activo = 1";
        $params = $this->params($p);
        $params[':id'] = $id;
        unset($params[':activo']);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function verificarDisponibilidad(array $items): array
    {
        $resultado = [];
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, stock, precio FROM productos WHERE id = :id AND activo = 1 LIMIT 1 FOR UPDATE"
        );

        foreach ($items as $item) {
            $productoId = (int) ($item['producto_id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $stmt->execute([':id' => $productoId]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception("Producto no encontrado (id {$productoId})", 404);
            }
            if ($cantidad <= 0) {
                throw new Exception("La cantidad debe ser mayor a cero", 400);
            }
            if ((int) $row['stock'] < $cantidad) {
                throw new Exception("Stock insuficiente para {$row['nombre']}", 400);
            }
            $resultado[] = [
                'producto_id'     => (int) $row['id'],
                'nombre'          => $row['nombre'],
                'cantidad'        => $cantidad,
                'precio_unitario' => (float) $row['precio'],
                'stock'           => (int) $row['stock'],
            ];
        }

        return $resultado;
    }

    public function descontarStock(array $items): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE productos SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad"
        );
        foreach ($items as $item) {
            $ok = $stmt->execute([
                ':cantidad' => (int) $item['cantidad'],
                ':id'       => (int) $item['producto_id'],
            ]);
            if (!$ok || $stmt->rowCount() === 0) {
                throw new Exception('No se pudo descontar el stock', 400);
            }
        }
    }

    public function resumenInventario(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_productos,
                    COALESCE(SUM(stock), 0) AS total_stock,
                    COALESCE(SUM(stock * precio), 0) AS valor_inventario
                FROM productos
                WHERE activo = 1";
        return $this->pdo->query($sql)->fetch() ?: [
            'total_productos'   => 0,
            'total_stock'       => 0,
            'valor_inventario'  => 0,
        ];
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function params(Producto $p): array
    {
        return [
            ':nombre'             => $p->nombre,
            ':codigo_barra'       => $p->codigo_barra,
            ':descripcion'        => $p->descripcion,
            ':categoria_id'       => $p->categoria_id,
            ':marca'              => $p->marca,
            ':proveedor_id'       => $p->proveedor_id,
            ':pasillo'            => $p->pasillo,
            ':stock'              => $p->stock,
            ':stock_minimo'       => $p->stock_minimo,
            ':precio'             => $p->precio,
            ':fecha_vencimiento'  => $p->fecha_vencimiento,
            ':imagen'             => $p->imagen,
            ':activo'             => $p->activo,
        ];
    }
}
