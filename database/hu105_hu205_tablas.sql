USE retail_db;

-- 1. Tablas de apoyo para HU-105 (3FN: Categorías y Ubicaciones en estante)
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS ubicacion_estante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pasillo VARCHAR(50) NOT NULL,
    estante VARCHAR(50) NOT NULL,
    UNIQUE KEY uk_pasillo_estante (pasillo, estante)
);

-- Ampliamos la tabla productos existente sin borrar datos de tus compañeros
ALTER TABLE productos
    ADD COLUMN IF NOT EXISTS categoria_id INT NULL,
    ADD COLUMN IF NOT EXISTS ubicacion_id INT NULL,
    ADD COLUMN IF NOT EXISTS stock_minimo INT NOT NULL DEFAULT 5,
    ADD CONSTRAINT fk_producto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_producto_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicacion_estante(id) ON DELETE SET NULL;

-- Datos iniciales de ejemplo para probar rápido
INSERT IGNORE INTO categorias (id, nombre) VALUES (1, 'General'), (2, 'Abarrotes'), (3, 'Bebidas');
INSERT IGNORE INTO ubicacion_estante (id, pasillo, estante) VALUES (1, 'Pasillo A', 'Estante 1'), (2, 'Pasillo B', 'Estante 2');

-- 2. Tabla de comprobantes para HU-205 (Boletas y Facturas asociadas a ventas)
CREATE TABLE IF NOT EXISTS comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    tipo_comprobante ENUM('BOLETA', 'FACTURA') NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    tipo_documento_cliente ENUM('DNI', 'RUC') NOT NULL,
    numero_documento_cliente VARCHAR(11) NOT NULL,
    nombre_cliente VARCHAR(150) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_serie_numero (serie, numero),
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE
);
