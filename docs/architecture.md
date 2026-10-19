# Arquitectura del Sistema — Planet Color

> Documento producido en la Etapa 1 (análisis) y confirmado antes de generar
> código. Se actualiza si alguna etapa posterior obliga a ajustar algo aquí.

## 1. Arquitectura de 3 capas

```
┌──────────────────────┐
│   PRESENTACIÓN       │  HTML5 + CSS3 + JS vanilla (fetch)
└─────────┬────────────┘
          │ HTTP (JSON) — API REST
┌─────────▼─────────────┐
│ LÓGICA DE APLICACIÓN  │  PHP: routes → controllers → services → repositories
└─────────┬─────────────┘
          │ PDO (consultas preparadas)
┌─────────▼─────────────┐
│  DATOS / PERSISTENCIA │  MySQL 3FN
└───────────────────────┘
```

El frontend **nunca** accede a MySQL directamente. Todo pasa por la API REST.

## 2. Flujo de comunicación entre capas (genérico)

```
Navegador (products.html)
   → frontend/js/services/api.js → fetch('/api/products', {...})
      → backend/public/index.php (front controller)
         → backend/routes/products.routes.php
            → backend/controllers/ProductController.php   (valida entrada, HTTP)
               → backend/services/ProductService.php        (reglas de negocio)
                  → backend/repositories/ProductRepository.php (SQL con PDO)
                     → MySQL
```

Cada capa solo conoce a la inmediata inferior:
- El **controller** nunca escribe SQL.
- El **repository** nunca decide reglas de negocio.
- El **service** nunca conoce detalles de HTTP (headers, códigos de estado).

## 3. Explicación de cada carpeta

| Carpeta | Responsabilidad |
|---|---|
| `frontend/pages` | Una página HTML por módulo (login, dashboard, products, sales...) |
| `frontend/components` | Fragmentos HTML/JS reutilizables entre páginas (sidebar, modales, toast) |
| `frontend/js/services/api.js` | Único lugar que hace `fetch()`; el resto del JS nunca llama `fetch` directo |
| `frontend/js/modules` | Un módulo JS por página, orquesta UI + llamadas a `api.js` |
| `frontend/js/utils` | Funciones auxiliares puras (formato de moneda, validaciones de formulario) |
| `backend/public/index.php` | Front controller: recibe toda petición, aplica middleware, despacha a rutas |
| `backend/public/uploads` | Imágenes de productos subidas por el usuario |
| `backend/routes` | Mapea método HTTP + URL → método de controller. Sin lógica de negocio |
| `backend/controllers` | Traduce HTTP↔PHP: lee input, llama al service, arma respuesta JSON con código HTTP |
| `backend/services` | Reglas de negocio puras (ej. "una devolución repone stock y genera egreso") |
| `backend/repositories` | Único lugar con SQL (PDO + prepared statements), una clase por entidad |
| `backend/models` | Estructuras de datos planas (Producto, Venta, Factura, Ingreso, Egreso...) |
| `backend/middleware` | Autenticación por sesión, validación de payload, cabeceras CORS |
| `backend/config` | Conexión PDO y carga de variables de entorno |
| `backend/utils` | Helpers transversales (respuesta JSON estándar, validador genérico) |
| `database/` | Scripts SQL versionables y reproducibles |
| `docs/` | Contrato de API (OpenAPI) y este documento de arquitectura |

## 4. Módulos del sistema

1. **Administración**: login/logout, usuarios, roles
2. **Inventario**: productos, stock, notificación de vencimiento/stock mínimo
3. **Gestión comercial**: ventas (carrito/checkout), compras a proveedor, facturación
4. **Finanzas y reportes**: ingresos, egresos, reporte de inventario, cálculo de ganancias

## 5. Entidades principales de la base de datos

- `usuarios` (id, nombre, email, password_hash, rol_id, fecha_creacion)
- `roles` (id, nombre)
- `categorias` (id, nombre)
- `proveedores` (id, nombre, contacto)
- `productos` (id, nombre, codigo_barra, descripcion, categoria_id, marca, proveedor_id, pasillo, stock, precio, fecha_vencimiento, imagen)
- `clientes` (id, nombre, documento, email, telefono)
- `ventas` (id, cliente_id, usuario_id, fecha, metodo_pago, total)
- `detalle_venta` (id, venta_id, producto_id, cantidad, precio_unitario)
- `compras` (id, proveedor_id, usuario_id, fecha, total)
- `detalle_compra` (id, compra_id, producto_id, cantidad, precio_unitario)
- `facturas` (id, numero, tipo, fecha, ruc, razon_social, monto, estado, venta_id)
- `ingresos` (id, fecha, descripcion, monto, metodo_pago, usuario_id)
- `egresos` (id, fecha, descripcion, monto, motivo, usuario_id)

> Nota: esta versión difiere del MER original del Informe Final Nro 2 en que
> normaliza `categoria`, `proveedor` y `rol` como tablas propias (antes eran
> VARCHAR sueltos o no existían). Ver la sección "Contradicciones detectadas"
> más abajo para la justificación completa.

## 6. Relación frontend – API – backend – MySQL

Autenticación por **sesión de PHP** (`session_start()` + cookie httpOnly), no
JWT, para mantener el proyecto simple. El middleware de autenticación protege
cada endpoint salvo `/api/auth/login`.

## 7. Flujo de ejemplo: "Registrar una venta"

```
1. sales.html → botón "Confirmar pago"
2. js/modules/sales.js → api.post('/api/sales/checkout', {items, metodo_pago})
3. routes/sales.routes.php → SalesController::checkout()
4. SalesController valida payload → SalesService::checkout($items, $metodo)
5. SalesService:
   a. ProductRepository::verificarDisponibilidad($items)
   b. ProductRepository::descontarStock($items)
   c. IngresoRepository::registrar($total, $metodo)
   d. FacturaService::generar($venta)  (si aplica)
6. Controller responde 201 con {success, message, data: {venta, factura}}
7. sales.js recibe JSON, re-renderiza carrito y muestra notificación
```

## 8. Contradicciones detectadas entre el diseño previo (Informe Final Nro 2) y esta implementación

### 8.1 Normalización de catálogo

- **Elemento anterior:** MER (Figura 16) no tiene tablas `categorias`,
  `proveedores` ni `roles`; `categoria`, `proveedor` y `marca` son VARCHAR
  sueltos en `productos`.
- **Problema:** viola la 3FN exigida en la especificación (redundancia si el
  mismo proveedor se repite en cientos de productos).
- **Propuesta:** normalizar `categorias` y `proveedores` como tablas con FK;
  `marca` se mantiene como VARCHAR (atributo simple, sin CRUD propio).
- **Justificación:** cumple 3FN sin sobreingeniería.

### 8.2 Roles de usuario

- **Elemento anterior:** RF-4.1 pide "gestión de permisos de usuario" pero el
  MER no incluye tabla `roles` ni columna de rol en `usuarios`.
- **Problema:** sin eso no se puede diferenciar Administrador/Vendedor/
  Repositor, como exige el DCU.2 (Gestión de Inventario).
- **Propuesta:** agregar tabla `roles` y columna `rol_id` en `usuarios`.
- **Justificación:** prerrequisito técnico directo de un requerimiento ya
  aprobado.

### 8.3 Diagrama de clases del prototipo vs. capas PHP

- **Elemento anterior:** el Diagrama de Clases (Figura 17) usa `AppController`,
  `ProductsController`, `FinanceController` con métodos de UI
  (`handleLogin`, `showToast`, `openModal`) — es el diseño del prototipo
  HTML/JS, no una arquitectura backend en capas.
- **Problema:** esos métodos de presentación no son trasladables a un backend
  PHP sin mezclar responsabilidades.
- **Propuesta:** dividir en `Controller/Service/Repository` por recurso
  (`Product`, `Sales`, `Finance`, `Invoice`, `Auth`); los métodos de UI quedan
  solo en el frontend JS.
- **Justificación:** traducción natural del mismo diseño a un cliente-servidor
  real, sin perder la intención original.

### 8.4 Columnas condicionales de pago

- **Elemento anterior:** `ingresos` tenía columnas de pago (
  `efectivo_recibido`, `tarjeta_ultimos_cuatro`, etc.) directamente en la
  tabla, que solo aplican según `metodo_pago`.
- **Decisión:** se mantienen como columnas nulleables en `ingresos` por
  simplicidad académica, en vez de crear una tabla `pagos_detalle` aparte.
- **Justificación:** balance entre pureza normativa y "no sobreingeniería".

## 9. Orden de desarrollo

Ver la tabla de checklist en `README.md`.
