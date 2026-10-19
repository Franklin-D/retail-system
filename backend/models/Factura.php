<?php
class Factura
{
    public ?int $id;
    public string $numero;
    public string $tipo;
    public ?string $fecha;
    public string $ruc;
    public string $razon_social;
    public ?string $email_cliente;
    public ?string $descripcion_servicio;
    public float $monto;
    public string $estado;
    public ?int $venta_id;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->numero = $data['numero'] ?? '';
        $this->tipo = $data['tipo'] ?? 'boleta';
        $this->fecha = $data['fecha'] ?? null;
        $this->ruc = $data['ruc'] ?? '00000000000';
        $this->razon_social = $data['razon_social'] ?? 'Cliente Genérico';
        $this->email_cliente = $data['email_cliente'] ?? null;
        $this->descripcion_servicio = $data['descripcion_servicio'] ?? null;
        $this->monto = (float) ($data['monto'] ?? 0);
        $this->estado = $data['estado'] ?? 'Emitido';
        $this->venta_id = isset($data['venta_id']) ? (int) $data['venta_id'] : null;
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'numero'                => $this->numero,
            'tipo'                  => $this->tipo,
            'fecha'                 => $this->fecha,
            'ruc'                   => $this->ruc,
            'razon_social'          => $this->razon_social,
            'email_cliente'         => $this->email_cliente,
            'descripcion_servicio'  => $this->descripcion_servicio,
            'monto'                 => $this->monto,
            'estado'                => $this->estado,
            'venta_id'              => $this->venta_id,
        ];
    }
}
