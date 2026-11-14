-- =============================================================================
-- seed.sql — Datos iniciales de prueba para "planet_color"
-- =============================================================================
-- Este archivo se completa en la ETAPA 3 del proyecto.
-- Contendrá datos FICTICIOS de ejemplo: un rol de administrador, un usuario
-- demo, algunas categorías, proveedores y productos de prueba (basados en
-- las imágenes de muestra del prototipo original: pelota, bebidas, papelería).
-- No se incluyen datos reales ni contraseñas reales.
-- =============================================================================
-- =============================================================================
-- seed.sql — Datos iniciales de prueba para "planet_color"
-- =============================================================================
-- Todos los datos son FICTICIOS, pensados solo para poder probar el
-- sistema localmente. No hay contraseñas ni información real de personas
-- o empresas. Ejecutar DESPUÉS de schema.sql.
-- =============================================================================

USE planet_color;

-- =============================================================================
-- ROLES
-- =============================================================================
INSERT INTO roles (id, nombre, descripcion) VALUES
  (1, 'administrador', 'Acceso total: inventario, ventas, compras, finanzas y reportes'),
  (2, 'vendedor',      'Acceso al punto de venta (compra de productos / checkout)'),
  (3, 'repositor',     'Acceso a registro de ingreso de mercadería y actualización de stock');

-- =============================================================================
-- USUARIOS
-- -----------------------------------------------------------------------
-- Contraseña de TODOS los usuarios de prueba: "admin123"
-- Hash generado con password_hash('admin123', PASSWORD_BCRYPT) en PHP.
-- ¡Cambiar en cualquier entorno que no sea de desarrollo local!
-- =============================================================================
INSERT INTO usuarios (id, nombre, email, password_hash, rol_id, activo) VALUES
  (1, 'Administrador Demo', 'admin@planetcolor.test',   '$2y$10$bV74tgUId/ZrH1nV5La4G.0e0snd/UXLW4X5XDpz6HCyaHn/wLpsK', 1, 1),
  (2, 'Vendedor Demo',      'vendedor@planetcolor.test','$2y$10$bV74tgUId/ZrH1nV5La4G.0e0snd/UXLW4X5XDpz6HCyaHn/wLpsK', 2, 1),
  (3, 'Repositor Demo',     'repositor@planetcolor.test','$2y$10$bV74tgUId/ZrH1nV5La4G.0e0snd/UXLW4X5XDpz6HCyaHn/wLpsK', 3, 1);

-- =============================================================================
-- CATEGORÍAS
-- =============================================================================
INSERT INTO categorias (id, nombre, descripcion) VALUES
  (1, 'Bebidas',   'Gaseosas, rehidratantes y bebidas envasadas'),
  (2, 'Oficina',   'Papelería y útiles de oficina/escolares'),
  (3, 'Deportes',  'Artículos deportivos'),
  (4, 'Abarrotes', 'Productos de consumo básico del hogar');

-- =============================================================================
-- PROVEEDORES (nombres ficticios, no corresponden a empresas reales)
-- =============================================================================
INSERT INTO proveedores (id, nombre, contacto, telefono, email) VALUES
  (1, 'Distribuidora Andina SAC',      'Carlos Vera',   '952000111', 'ventas@andina-demo.test'),
  (2, 'Papelera Sur EIRL',             'Marisol Quiroz','952000222', 'contacto@papelerasur-demo.test'),
  (3, 'Bebidas del Altiplano SRL',     'Jorge Mamani',  '952000333', 'pedidos@altiplano-demo.test');

-- =============================================================================
-- CLIENTES
-- =============================================================================
INSERT INTO clientes (id, nombre, documento, email, telefono) VALUES
  (1, 'Cliente Genérico',      NULL,          NULL,                       NULL),
  (2, 'Juana Pérez Condori',   '12345678',    'juana.perez@demo.test',    '951111111'),
  (3, 'Comercial Los Andes SAC','20123456789','ventas@losandes-demo.test','951222222');

-- =============================================================================
-- PRODUCTOS
-- -----------------------------------------------------------------------
-- Los mismos 6 productos de muestra usados en el prototipo original,
-- ahora normalizados contra categorias/proveedores. Las imágenes referencian
-- solo el nombre de archivo; la ruta completa se resuelve en el backend
-- (Etapa 7) sirviendo backend/public/uploads/seed/<archivo>.
-- =============================================================================
INSERT INTO productos
  (id, nombre, codigo_barra, descripcion, categoria_id, marca, proveedor_id, pasillo, stock, stock_minimo, precio, fecha_vencimiento, imagen) VALUES
  (1, 'Pelota de fútbol N°5',        '7501234567890', 'Pelota oficial N°5, cuero sintético', 3, 'Genérica', 1, 'A-3', 25,  5, 45.90, NULL,         'Pelota.jpg'),
  (2, 'Pepsi 500ml',                 '7501234500012', 'Bebida gaseosa 500ml',                1, 'Pepsi',    3, 'B-1', 80, 10,  3.50, '2026-12-31', 'pepsi.jpg'),
  (3, 'Powerade 500ml',              '7501234500029', 'Bebida rehidratante',                 1, 'Powerade', 3, 'B-1', 40, 10,  4.20, '2026-10-15', 'powerade.jpg'),
  (4, 'Resma de papel bond A4',      '7501234500036', 'Resma 500 hojas 75g',                 2, 'Genérica', 2, 'C-2', 60, 10, 14.50, NULL,         'resma.jpg'),
  (5, 'Cuadernillo cuadriculado',    '7501234500043', 'Cuadernillo 50 hojas',                2, 'Genérica', 2, 'C-2', 120, 15,  2.80, NULL,         'Cuadernillo.jpg'),
  (6, 'Juego de reglas geométricas', '7501234500050', 'Set de reglas escuadras y transportador', 2, 'Genérica', 2, 'C-3', 35, 5,  6.90, NULL,         'reglas.jpg');

-- =============================================================================
-- EJEMPLO TRANSACCIONAL (opcional, para poder probar reportes desde ya)
-- -----------------------------------------------------------------------
-- Una venta de muestra: 2 Pepsi + 1 Pelota, pagada en efectivo por el
-- vendedor demo, con su ingreso y factura asociados.
-- =============================================================================
INSERT INTO ventas (id, cliente_id, usuario_id, fecha, motivo, metodo_pago, total) VALUES
  (1, 1, 2, '2026-09-10 10:30:00', 'Venta', 'Efectivo', 52.90);

INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES
  (1, 2, 2, 3.50),   -- 2 Pepsi
  (1, 1, 1, 45.90);  -- 1 Pelota

-- Reflejar el descuento de stock de la venta de ejemplo
UPDATE productos SET stock = stock - 2 WHERE id = 2;
UPDATE productos SET stock = stock - 1 WHERE id = 1;

INSERT INTO ingresos (id, fecha, descripcion, monto, metodo_pago, venta_id, usuario_id) VALUES
  (1, '2026-09-10 10:30:00', 'Venta (Efectivo)', 52.90, 'Efectivo', 1, 2);

INSERT INTO facturas
  (id, numero, tipo, fecha, ruc, razon_social, email_cliente, descripcion_servicio, monto, estado, venta_id) VALUES
  (1, 'B-1000000001', 'boleta', '2026-09-10 10:31:00', '00000000000', 'Cliente Genérico', NULL, 'Venta de mostrador', 52.90, 'Emitido', 1);

-- =============================================================================
-- FIN seed.sql
-- =============================================================================