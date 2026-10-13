<?php
/**
 * models/Usuario.php
 * DTO que representa una fila de la tabla usuarios.
 */

class Usuario
{
    public ?int $id;
    public string $nombre;
    public string $email;
    public string $password;
    public string $rol;
    public int $estado;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->nombre = $data['nombre'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->rol = $data['rol'] ?? 'vendedor';
        $this->estado = isset($data['estado']) ? (int)$data['estado'] : 1;
    }

    public function toArrayWithoutPassword(): array {
        return [
            'id'     => $this->id,
            'nombre' => $this->nombre,
            'email'  => $this->email,
            'rol'    => $this->rol,
            'estado' => $this->estado,
        ];
    }
}