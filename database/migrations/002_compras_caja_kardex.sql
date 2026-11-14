-- =============================================================================
-- 002_compras_caja_kardex.sql
-- Soporta RF-2.1 / RF-2.2 (caja chica) y RF-1.11 (Kardex básico).
--
--   1. ingresos/egresos: motivo y comprobante para movimientos extraordinarios.
--   2. egresos.motivo deja de ser ENUM para permitir motivos libres.
--   3. movimientos_inventario: historial de entradas/salidas de stock.
--
-- Ejecutar UNA sola vez sobre la base "planet_color":
--   mysql -u root -p planet_color < database/migrations/002_compras_caja_kardex.sql
-- =============================================================================

ALTER TABLE ingresos
  ADD COLUMN motivo VARCHAR(100) NULL
    COMMENT 'Motivo del ingreso extraordinario de caja (RF-2.1)'
    AFTER descripcion,
  ADD COLUMN comprobante VARCHAR(100) NULL
    COMMENT 'Número o referencia del comprobante de caja'
    AFTER motivo;

ALTER TABLE egresos
  MODIFY COLUMN motivo VARCHAR(100) NOT NULL DEFAULT 'Otro'
    COMMENT 'Compra, devolución, gasto extraordinario, etc. (RF-2.2)',
  ADD COLUMN comprobante VARCHAR(100) NULL
    COMMENT 'Número o referencia del comprobante de caja'
    AFTER motivo;

CREATE TABLE movimientos_inventario (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id     INT UNSIGNED NOT NULL,
  usuario_id      INT UNSIGNED NOT NULL,
  fecha           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo            ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad        INT          NOT NULL COMMENT 'Positiva para entradas; negativa para salidas',
  stock_anterior  INT UNSIGNED NOT NULL,
  stock_nuevo     INT UNSIGNED NOT NULL,
  origen          ENUM('compra','venta','ajuste','inventario_inicial','devolucion') NOT NULL DEFAULT 'ajuste',
  referencia_id   INT UNSIGNED NULL COMMENT 'ID de la compra, venta o ajuste que originó el movimiento',
  motivo          VARCHAR(255) NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movimientos_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_movimientos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_movimientos_producto_fecha ON movimientos_inventario(producto_id, fecha);
CREATE INDEX idx_movimientos_fecha ON movimientos_inventario(fecha);
CREATE INDEX idx_movimientos_origen ON movimientos_inventario(origen, referencia_id);
