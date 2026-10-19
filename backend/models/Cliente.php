<?php
class Cliente
{
    public ?int $id;
    public string $nombre;
    public ?string $documento;
    public ?string $email;
    public ?string $telefono;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->nombre = $data['nombre'] ?? '';
        $this->documento = $data['documento'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->telefono = $data['telefono'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'documento' => $this->documento,
            'email'     => $this->email,
            'telefono'  => $this->telefono,
        ];
    }
}
