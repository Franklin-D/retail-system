-- =============================================================================
-- schema.sql — Estructura de la base de datos "planet_color"
-- =============================================================================
-- Este archivo se completa en la ETAPA 3 del proyecto.
-- Contendrá, en orden: creación de la base de datos, tablas de catálogo
-- (roles, categorias, proveedores), tablas principales (usuarios, productos,
-- clientes), tablas transaccionales (ventas, detalle_venta, compras,
-- detalle_compra, facturas, ingresos, egresos) y sus llaves foráneas.
--
-- Ver docs/architecture.md, sección 5, para el listado de entidades acordado.
-- =============================================================================
-- =============================================================================
-- schema.sql — Estructura de la base de datos "planet_color"
-- =============================================================================
-- Motor: MySQL 8.0+ / InnoDB / utf8mb4
-- Normalización: Tercera Forma Normal (3FN), según lo acordado en la
-- Etapa 1 (ver docs/architecture.md, sección 8, para la justificación de
-- las diferencias frente al MER original del Informe Final Nro 2).
--
-- Orden de creación: catálogos -> entidades principales -> transaccionales,
-- respetando las dependencias de llaves foráneas.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS planet_color
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE planet_color;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------
-- Limpieza (permite re-ejecutar el script en un entorno de desarrollo)
-- -----------------------------------------------------------------------
DROP TABLE IF EXISTS egresos;
DROP TABLE IF EXISTS ingresos;
DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS detalle_compra;
DROP TABLE IF EXISTS compras;
DROP TABLE IF EXISTS detalle_venta;
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS proveedores;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. CATÁLOGOS (sin dependencias)
-- =============================================================================

-- ---------------------------------------------------------------- roles ----
CREATE TABLE roles (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(50)  NOT NULL UNIQUE,
  descripcion   VARCHAR(255) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ----------------------------------------------------------- categorias ----
CREATE TABLE categorias (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL UNIQUE,
  descripcion   VARCHAR(255) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------- proveedores ----
CREATE TABLE proveedores (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(150) NOT NULL,
  contacto      VARCHAR(150) NULL,
  telefono      VARCHAR(20)  NULL,
  email         VARCHAR(150) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ------------------------------------------------------------ clientes ----
CREATE TABLE clientes (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(150) NOT NULL,
  documento     VARCHAR(20)  NULL UNIQUE COMMENT 'DNI/RUC, opcional para clientes genéricos',
  email         VARCHAR(150) NULL,
  telefono      VARCHAR(20)  NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- =============================================================================
-- 2. ENTIDADES PRINCIPALES (dependen de los catálogos)
-- =============================================================================

-- ------------------------------------------------------------ usuarios ----
CREATE TABLE usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL COMMENT 'bcrypt, nunca texto plano',
  rol_id         INT UNSIGNED NOT NULL,
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (rol_id) REFERENCES roles(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_usuarios_rol ON usuarios(rol_id);

-- ------------------------------------------------------------ productos ----
CREATE TABLE productos (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(150)   NOT NULL,
  codigo_barra       VARCHAR(50)    NOT NULL UNIQUE,
  descripcion        TEXT           NULL,
  categoria_id       INT UNSIGNED   NOT NULL,
  marca              VARCHAR(100)   NULL,
  proveedor_id       INT UNSIGNED   NOT NULL,
  pasillo            VARCHAR(50)    NULL COMMENT 'Ubicación física en estantería',
  stock              INT UNSIGNED   NOT NULL DEFAULT 0,
  stock_minimo       INT UNSIGNED   NOT NULL DEFAULT 5 COMMENT 'Umbral para notificación de stock mínimo (RF-1.7)',
  precio             DECIMAL(10,2)  NOT NULL,
  fecha_vencimiento  DATE           NULL,
  imagen             VARCHAR(255)   NULL COMMENT 'Ruta relativa dentro de backend/public/uploads',
  activo             TINYINT(1)     NOT NULL DEFAULT 1 COMMENT 'Baja lógica en vez de DELETE físico',
  estado             ENUM('borrador','publicado') NOT NULL DEFAULT 'publicado' COMMENT 'borrador = pre-carga de empleados (oculto); publicado = visible en tienda',
  created_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_productos_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_productos_proveedor
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_estado ON productos(estado, activo);
CREATE INDEX idx_productos_proveedor ON productos(proveedor_id);
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_productos_vencimiento ON productos(fecha_vencimiento);

-- =============================================================================
-- 3. TABLAS TRANSACCIONALES
-- =============================================================================

-- --------------------------------------------------------------- ventas ----
CREATE TABLE ventas (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id    INT UNSIGNED NULL COMMENT 'NULL = cliente genérico / venta de mostrador',
  usuario_id    INT UNSIGNED NOT NULL COMMENT 'Vendedor que registró la venta',
  fecha         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  motivo        ENUM('Venta','Devolucion','Producto danado','Producto vencido') NOT NULL DEFAULT 'Venta',
  metodo_pago   ENUM('Efectivo','Tarjeta','Yape','Plin') NOT NULL DEFAULT 'Efectivo',
  total         DECIMAL(10,2) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ventas_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_ventas_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_ventas_fecha ON ventas(fecha);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);

-- --------------------------------------------------------- detalle_venta ----
CREATE TABLE detalle_venta (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venta_id         INT UNSIGNED   NOT NULL,
  producto_id      INT UNSIGNED   NOT NULL,
  cantidad         INT UNSIGNED   NOT NULL,
  precio_unitario  DECIMAL(10,2)  NOT NULL COMMENT 'Precio al momento de la venta (histórico, no referenciar productos.precio)',
  CONSTRAINT fk_detalle_venta_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_venta_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_detalle_venta_venta ON detalle_venta(venta_id);
CREATE INDEX idx_detalle_venta_producto ON detalle_venta(producto_id);

-- -------------------------------------------------------------- compras ----
CREATE TABLE compras (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proveedor_id  INT UNSIGNED NOT NULL,
  usuario_id    INT UNSIGNED NOT NULL COMMENT 'Repositor/administrador que registró el ingreso de mercadería',
  fecha         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total         DECIMAL(10,2) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_compras_proveedor
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_compras_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_compras_fecha ON compras(fecha);
CREATE INDEX idx_compras_proveedor ON compras(proveedor_id);

-- -------------------------------------------------------- detalle_compra ----
CREATE TABLE detalle_compra (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  compra_id        INT UNSIGNED   NOT NULL,
  producto_id      INT UNSIGNED   NOT NULL,
  cantidad         INT UNSIGNED   NOT NULL,
  precio_unitario  DECIMAL(10,2)  NOT NULL COMMENT 'Precio de compra al momento de la transacción',
  CONSTRAINT fk_detalle_compra_compra
    FOREIGN KEY (compra_id) REFERENCES compras(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_compra_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_detalle_compra_compra ON detalle_compra(compra_id);
CREATE INDEX idx_detalle_compra_producto ON detalle_compra(producto_id);

-- -------------------------------------------------------------- facturas ----
CREATE TABLE facturas (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero                VARCHAR(50)   NOT NULL UNIQUE,
  tipo                  ENUM('factura','boleta','nota_credito','nota_debito','recibo_honorarios') NOT NULL,
  fecha                 DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ruc                   VARCHAR(20)   NOT NULL,
  razon_social          VARCHAR(150)  NOT NULL,
  email_cliente         VARCHAR(150)  NULL,
  descripcion_servicio  TEXT          NULL,
  monto                 DECIMAL(10,2) NOT NULL,
  estado                ENUM('Emitido','Anulado') NOT NULL DEFAULT 'Emitido',
  venta_id              INT UNSIGNED  NULL COMMENT 'NULL cuando la factura no proviene de una venta del POS (ej. recibo por honorarios)',
  created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_facturas_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_facturas_fecha ON facturas(fecha);
CREATE INDEX idx_facturas_venta ON facturas(venta_id);

-- -------------------------------------------------------------- ingresos ----
CREATE TABLE ingresos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  descripcion   VARCHAR(255)  NOT NULL,
  monto         DECIMAL(10,2) NOT NULL,
  metodo_pago   ENUM('Efectivo','Tarjeta','Yape','Plin') NOT NULL DEFAULT 'Efectivo',
  venta_id      INT UNSIGNED  NULL COMMENT 'Venta que originó este ingreso, si aplica',
  usuario_id    INT UNSIGNED  NOT NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ingresos_venta
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ingresos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_ingresos_fecha ON ingresos(fecha);

-- --------------------------------------------------------------- egresos ----
CREATE TABLE egresos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  descripcion   VARCHAR(255)  NOT NULL,
  monto         DECIMAL(10,2) NOT NULL,
  motivo        ENUM('Compra','Devolucion','Producto danado','Producto vencido','Otro') NOT NULL DEFAULT 'Otro',
  compra_id     INT UNSIGNED  NULL COMMENT 'Compra que originó este egreso, si aplica',
  usuario_id    INT UNSIGNED  NOT NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_egresos_compra
    FOREIGN KEY (compra_id) REFERENCES compras(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_egresos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE INDEX idx_egresos_fecha ON egresos(fecha);

-- =============================================================================
-- FIN schema.sql
-- =============================================================================