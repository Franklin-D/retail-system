CREATE DATABASE IF NOT EXISTS retail_db;
USE retail_db;

CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  codigo VARCHAR(50) UNIQUE,
  costo DECIMAL(10,2) NOT NULL,
  precio_venta DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT,
  cantidad INT,
  total DECIMAL(10,2),
  ganancia DECIMAL(10,2),
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- 1. Tabla de Roles (RBAC)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (id, nombre) VALUES 
(1, 'Administrador'), 
(2, 'Vendedor'), 
(3, 'Repositor');

-- 2. Tabla de Usuarios con soporte RBAC y 2FA
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL DEFAULT 2,
    secret_2fa VARCHAR(255) NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Usuario administrador de prueba (Password: Admin123* | Código 2FA: 123456)
INSERT IGNORE INTO usuarios (id, username, password_hash, rol_id, secret_2fa) VALUES 
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '123456');

-- 3. Bitácora de Auditoría Inmutable
CREATE TABLE IF NOT EXISTS auditoria_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    detalles TEXT,
    ip_origen VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);