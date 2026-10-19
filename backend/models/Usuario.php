<?php
class Usuario
{
    public ?int $id;
    public string $nombre;
    public string $email;
    public string $password;
    public int $rol_id;
    public string $rol;
    public int $activo;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->nombre = $data['nombre'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->password = $data['password_hash'] ?? ($data['password'] ?? '');
        $this->rol_id = (int) ($data['rol_id'] ?? 0);
        $this->rol = $data['rol'] ?? ($data['rol_nombre'] ?? '');
        $this->activo = isset($data['activo']) ? (int) $data['activo'] : 1;
    }

    public function toArrayWithoutPassword(): array
    {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'email'  => $this->email,
            'rol_id' => $this->rol_id,
            'rol'    => $this->rol,
            'activo' => $this->activo,
        ];
    }
}
