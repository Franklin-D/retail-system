<?php
class Proveedor
{
    public ?int $id;
    public string $nombre;
    public ?string $contacto;
    public ?string $telefono;
    public ?string $email;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->nombre = $data['nombre'] ?? '';
        $this->contacto = $data['contacto'] ?? null;
        $this->telefono = $data['telefono'] ?? null;
        $this->email = $data['email'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'nombre'   => $this->nombre,
            'contacto' => $this->contacto,
            'telefono' => $this->telefono,
            'email'    => $this->email,
        ];
    }
}
