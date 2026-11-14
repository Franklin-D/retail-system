-- Crea el inventario por lotes y migra fechas de vencimiento existentes.
-- Ejecutar una sola vez sobre la base "planet_color":
--   mysql -u root -p planet_color < database/migrations/002_lotes_vencimiento.sql

CREATE TABLE IF NOT EXISTS lotes (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id           INT UNSIGNED NOT NULL,
  codigo_lote           VARCHAR(64) NOT NULL,
  stock                 INT UNSIGNED NOT NULL DEFAULT 0,
  fecha_vencimiento     DATE NOT NULL,
  descuento_porcentaje  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_lotes_producto_codigo UNIQUE (producto_id, codigo_lote),
  CONSTRAINT chk_lotes_descuento CHECK (descuento_porcentaje <= 100),
  CONSTRAINT fk_lotes_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_lotes_vencimiento_stock (fecha_vencimiento, stock)
) ENGINE = InnoDB;

-- Conserva el stock y la fecha que ya vivían en productos como un lote inicial.
INSERT INTO lotes (producto_id, codigo_lote, stock, fecha_vencimiento)
SELECT p.id, CONCAT('LEGACY-', p.id), p.stock, p.fecha_vencimiento
FROM productos p
WHERE p.fecha_vencimiento IS NOT NULL
  AND p.stock > 0
  AND NOT EXISTS (
    SELECT 1 FROM lotes l WHERE l.producto_id = p.id AND l.codigo_lote = CONCAT('LEGACY-', p.id)
  );
