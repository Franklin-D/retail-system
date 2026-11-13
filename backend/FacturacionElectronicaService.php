<?php
// backend/FacturacionElectronicaService.php

class FacturacionElectronicaService
{
    private PDO $pdo;
    private const IGV_RATE = 0.18;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Valida DNI (8 dígitos numéricos) o RUC (11 dígitos numéricos).
     */
    public function validarDocumento(string $tipoDoc, string $numeroDoc): void
    {
        $tipoDoc = strtoupper(trim($tipoDoc));
        $numeroDoc = trim($numeroDoc);

        if ($tipoDoc === 'DNI') {
            if (!preg_match('/^\d{8}$/', $numeroDoc)) {
                throw new InvalidArgumentException('El DNI debe contener exactamente 8 dígitos numéricos.');
            }
        } elseif ($tipoDoc === 'RUC') {
            if (!preg_match('/^\d{11}$/', $numeroDoc)) {
                throw new InvalidArgumentException('El RUC debe contener exactamente 11 dígitos numéricos.');
            }
        } else {
            throw new InvalidArgumentException('Tipo de documento inválido. Use DNI o RUC.');
        }
    }

    /**
     * Calcula Subtotal, IGV (18%) y Total a partir del monto total de la venta (incluido IGV).
     */
    public function calcularMontos(float $montoTotal): array
    {
        if ($montoTotal <= 0) {
            throw new InvalidArgumentException('El monto total de la venta debe ser mayor a cero.');
        }

        $subtotal = round($montoTotal / (1 + self::IGV_RATE), 2);
        $igv      = round($montoTotal - $subtotal, 2);
        $total    = round($montoTotal, 2);

        return [
            'subtotal' => $subtotal,
            'igv'      => $igv,
            'total'    => $total
        ];
    }

    /**
     * Emite y guarda el comprobante electrónico asociado a un ID de venta en MySQL.
     */
    public function emitirComprobante(int $ventaId, string $tipoComprobante, string $tipoDoc, string $numeroDoc, string $nombreCliente): array
    {
        $tipoComprobante = strtoupper(trim($tipoComprobante));
        $tipoDoc         = strtoupper(trim($tipoDoc));
        $nombreCliente   = trim($nombreCliente);

        if (!in_array($tipoComprobante, ['BOLETA', 'FACTURA'], true)) {
            throw new InvalidArgumentException('El tipo de comprobante debe ser BOLETA o FACTURA.');
        }

        if ($tipoComprobante === 'FACTURA' && $tipoDoc !== 'RUC') {
            throw new InvalidArgumentException('Para emitir una FACTURA el cliente debe identificarse con RUC.');
        }

        if ($nombreCliente === '') {
            throw new InvalidArgumentException('El nombre o razón social del cliente es obligatorio.');
        }

        $this->validarDocumento($tipoDoc, $numeroDoc);

        // Verificar que la venta exista
        $stmtVenta = $this->pdo->prepare("SELECT id, total, cantidad, producto_id FROM ventas WHERE id = :id LIMIT 1");
        $stmtVenta->execute([':id' => $ventaId]);
        $venta = $stmtVenta->fetch();

        if (!$venta) {
            throw new RuntimeException("No existe la venta con ID #{$ventaId}.");
        }

        // Evitar duplicar comprobante para la misma venta
        $stmtDup = $this->pdo->prepare("SELECT id FROM comprobantes WHERE venta_id = :venta_id LIMIT 1");
        $stmtDup->execute([':venta_id' => $ventaId]);
        if ($stmtDup->fetch()) {
            throw new RuntimeException("La venta #{$ventaId} ya tiene un comprobante emitido.");
        }

        $montos = $this->calcularMontos((float)$venta['total']);
        $serie  = ($tipoComprobante === 'FACTURA') ? 'F001' : 'B001';

        // Generar correlativo siguiente
        $stmtCorr = $this->pdo->prepare("SELECT COUNT(*) + 1 AS siguiente FROM comprobantes WHERE serie = :serie");
        $stmtCorr->execute([':serie' => $serie]);
        $siguiente = (int)$stmtCorr->fetch()['siguiente'];
        $numero = str_pad((string)$siguiente, 8, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO comprobantes 
                (venta_id, tipo_comprobante, serie, numero, tipo_documento_cliente, numero_documento_cliente, nombre_cliente, subtotal, igv, total)
                VALUES 
                (:venta_id, :tipo_comprobante, :serie, :numero, :tipo_doc, :num_doc, :nombre, :subtotal, :igv, :total)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':venta_id'         => $ventaId,
            ':tipo_comprobante' => $tipoComprobante,
            ':serie'            => $serie,
            ':numero'           => $numero,
            ':tipo_doc'         => $tipoDoc,
            ':num_doc'          => $numeroDoc,
            ':nombre'           => $nombreCliente,
            ':subtotal'         => $montos['subtotal'],
            ':igv'              => $montos['igv'],
            ':total'            => $montos['total']
        ]);

        return $this->obtenerComprobante((int)$this->pdo->lastInsertId());
    }

    /**
     * Obtiene los datos completos del comprobante y el detalle del producto vendido para imprimir.
     */
    public function obtenerComprobante(int $comprobanteId): array
    {
        $sql = "SELECT 
                    c.*,
                    v.cantidad,
                    v.fecha AS fecha_venta,
                    p.nombre AS producto_nombre,
                    p.codigo AS producto_codigo,
                    p.precio_venta AS precio_unitario
                FROM comprobantes c
                INNER JOIN ventas v ON c.venta_id = v.id
                LEFT JOIN productos p ON v.producto_id = p.id
                WHERE c.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $comprobanteId]);
        $data = $stmt->fetch();

        if (!$data) {
            throw new RuntimeException('Comprobante no encontrado.');
        }

        return $data;
    }
}
