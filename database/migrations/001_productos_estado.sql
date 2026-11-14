-- =============================================================================
-- 001_productos_estado.sql
-- Agrega el estado del producto para soportar la PRE-CARGA (borradores).
--
--   estado = 'publicado' -> visible en Productos, Ventas, Reportes (comportamiento normal)
--   estado = 'borrador'  -> pre-carga de empleados; OCULTO en todo lo anterior
--
-- Se mantiene `activo` para la baja lógica (eliminar). Son dos conceptos distintos:
--   activo = 0                      -> producto eliminado
--   activo = 1 AND estado='borrador' -> aún no dado de alta
--
-- Los productos que ya existen quedan automáticamente como 'publicado'
-- (por el DEFAULT), así que nada cambia para el catálogo actual.
--
-- Ejecutar UNA sola vez sobre la base "planet_color":
--   mysql -u root -p planet_color < database/migrations/001_productos_estado.sql
-- =============================================================================

ALTER TABLE productos
  ADD COLUMN estado ENUM('borrador', 'publicado') NOT NULL DEFAULT 'publicado'
    COMMENT 'borrador = pre-carga de empleados (oculto); publicado = visible en tienda'
    AFTER activo;

CREATE INDEX idx_productos_estado ON productos(estado, activo);
