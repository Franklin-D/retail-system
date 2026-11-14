<?php
class AuditoriaLogService {
    private $conn;

    public function __construct($conn) {
        $this->conn =$conn;
    }

    public function registrar($usuario_id, $accion,$detalles) {
        $ip =$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $usuario_id = intval($usuario_id);$accion = mysqli_real_escape_string($this->conn,$accion);
        $detalles = mysqli_real_escape_string($this->conn, $detalles);$ip = mysqli_real_escape_string($this->conn,$ip);

        $sql = "INSERT INTO auditoria_logs (usuario_id, accion, detalles, ip_origen) 
                VALUES ($usuario_id, '$accion', '$detalles', '$ip')";
        return mysqli_query($this->conn,$sql);
    }
}
?>