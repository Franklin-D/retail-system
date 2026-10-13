<?php
/**
 * //sistema-retail/config/database.php
 * -----------------------------------------------------------------------
 * Responsabilidad: crear y devolver una única instancia PDO conectada a
 * MySQL, usando las credenciales cargadas desde .env (config/env.php).
 *
 * Implementación real: ETAPA 4.
 *
 * Uso previsto desde un repository:
 *   require_once __DIR__ . '/../config/database.php';
 *   $pdo = Database::getConnection();
 * -----------------------------------------------------------------------
 */

class Database {
    private string $host = 'localhost';
    private string $db_name = 'planet_color';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';
    private ?PDO $conn = null;

    public function getConnection(): ?PDO {
        $this->conn = null;

        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error"   => "Error de conexión a la base de datos: " . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $this->conn;
    }
}
