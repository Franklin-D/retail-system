<?php

namespace Retail\Repositories;

use Exception;
use PDO;
use PDOException;
use Retail\Config\Database;

class LoteRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findProximosAVencer(string $limite): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.id AS lote_id, l.producto_id, l.codigo_lote, l.stock,
                    l.fecha_vencimiento, l.descuento_porcentaje,
                    p.nombre, p.codigo_barra, p.precio, p.pasillo
             FROM lotes l
             INNER JOIN productos p ON p.id = l.producto_id
             WHERE p.activo = 1 AND p.estado = 'publicado'
               AND l.stock > 0 AND l.fecha_vencimiento <= :limite
             ORDER BY l.fecha_vencimiento ASC, p.nombre ASC, l.id ASC"
        );
        $stmt->execute([':limite' => $limite]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT l.id AS lote_id, l.producto_id, l.codigo_lote, l.stock,
                    l.fecha_vencimiento, l.descuento_porcentaje,
                    p.nombre, p.codigo_barra, p.precio, p.pasillo
             FROM lotes l
             INNER JOIN productos p ON p.id = l.producto_id
             WHERE l.id = :id AND p.activo = 1 AND p.estado = 'publicado'
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $productoId, string $codigo, int $stock, string $fecha): int
    {
        $iniciaTransaccion = !$this->pdo->inTransaction();
        if ($iniciaTransaccion) {
            $this->pdo->beginTransaction();
        }

        try {
            $producto = $this->pdo->prepare(
                "SELECT stock FROM productos
                 WHERE id = :id AND activo = 1 AND estado = 'publicado'
                 LIMIT 1 FOR UPDATE"
            );
            $producto->execute([':id' => $productoId]);
            $row = $producto->fetch();
            if (!$row) {
                throw new Exception('Producto no encontrado o no disponible', 404);
            }

            $lotes = $this->pdo->prepare(
                'SELECT COALESCE(SUM(stock), 0) FROM lotes WHERE producto_id = :producto_id'
            );
            $lotes->execute([':producto_id' => $productoId]);
            $stockEnLotes = (int) $lotes->fetchColumn();
            if ($stockEnLotes + $stock > (int) $row['stock']) {
                throw new Exception('La cantidad del lote supera el stock disponible sin asignar', 422);
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO lotes (producto_id, codigo_lote, stock, fecha_vencimiento)
                 VALUES (:producto_id, :codigo_lote, :stock, :fecha_vencimiento)'
            );
            $stmt->execute([
                ':producto_id' => $productoId,
                ':codigo_lote' => $codigo,
                ':stock' => $stock,
                ':fecha_vencimiento' => $fecha,
            ]);
            $id = (int) $this->pdo->lastInsertId();

            if ($iniciaTransaccion) {
                $this->pdo->commit();
            }
            return $id;
        } catch (PDOException $e) {
            if ($iniciaTransaccion && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                throw new Exception('Ese código de lote ya está registrado para el producto', 409);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($iniciaTransaccion && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function aplicarDescuento(int $id, int $porcentaje): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE lotes SET descuento_porcentaje = :porcentaje WHERE id = :id'
        );
        $stmt->execute([':porcentaje' => $porcentaje, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
