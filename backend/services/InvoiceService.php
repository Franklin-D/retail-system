<?php
require_once __DIR__ . '/../repositories/InvoiceRepository.php';

class InvoiceService
{
    private InvoiceRepository $repo;

    public function __construct()
    {
        $this->repo = new InvoiceRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }

    public function generar(array $venta): array
    {
        $id = $this->repo->create([
            'numero'               => $this->repo->siguienteNumero('boleta'),
            'tipo'                 => 'boleta',
            'ruc'                  => $venta['ruc'] ?? '00000000000',
            'razon_social'         => $venta['razon_social'] ?? 'Cliente Genérico',
            'descripcion_servicio' => 'Venta de mostrador',
            'monto'                => $venta['total'],
            'venta_id'             => $venta['id'],
        ]);
        $factura = $this->repo->findById($id);
        if (!$factura) {
            throw new Exception('No se pudo generar la factura', 500);
        }
        return $factura;
    }
}
