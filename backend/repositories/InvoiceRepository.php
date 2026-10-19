<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Factura.php';

class InvoiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM facturas ORDER BY fecha DESC, id DESC");
        return array_map(fn($row) => (new Factura($row))->toArray(), $stmt->fetchAll());
    }

    public function siguienteNumero(string $tipo = 'boleta'): string
    {
        $prefijo = $tipo === 'factura' ? 'F-' : 'B-';
        $stmt = $this->pdo->prepare(
            "SELECT numero FROM facturas WHERE numero LIKE :prefijo ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':prefijo' => $prefijo . '%']);
        $ultimo = $stmt->fetchColumn();
        $n = 1;
        if ($ultimo && preg_match('/(\d+)$/', (string) $ultimo, $m)) {
            $n = ((int) $m[1]) + 1;
        }
        return $prefijo . str_pad((string) $n, 10, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO facturas
                    (numero, tipo, fecha, ruc, razon_social, email_cliente, descripcion_servicio, monto, estado, venta_id)
                VALUES
                    (:numero, :tipo, NOW(), :ruc, :razon_social, :email_cliente, :descripcion_servicio, :monto, :estado, :venta_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':numero'               => $data['numero'],
            ':tipo'                 => $data['tipo'] ?? 'boleta',
            ':ruc'                  => $data['ruc'] ?? '00000000000',
            ':razon_social'         => $data['razon_social'] ?? 'Cliente Genérico',
            ':email_cliente'        => $data['email_cliente'] ?? null,
            ':descripcion_servicio' => $data['descripcion_servicio'] ?? 'Venta de mostrador',
            ':monto'                => $data['monto'],
            ':estado'               => 'Emitido',
            ':venta_id'             => $data['venta_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM facturas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? (new Factura($row))->toArray() : null;
    }
}
