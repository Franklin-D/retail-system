# database/

- `schema.sql` — Estructura completa de la base de datos `planet_color`
  (tablas, llaves foráneas, índices).
- `seed.sql` — Datos iniciales de prueba (ficticios, sin contraseñas ni datos
  reales).
- `migrations/` — Cambios incrementales aplicados después del esquema base
  (por ejemplo, nuevas columnas o estados).

## Cómo aplicar los scripts

```bash
mysql -u root -p < schema.sql
mysql -u root -p < seed.sql
```

Las migraciones se aplican en orden numérico:

```bash
mysql -u root -p planet_color < migrations/001_productos_estado.sql
```

## Convenciones usadas

- Nombres de tabla en `snake_case`, plural (`productos`, `detalle_venta`).
- Toda tabla tiene `id` autoincremental como llave primaria.
- Toda fecha de auditoría usa `TIMESTAMP` con `DEFAULT CURRENT_TIMESTAMP`.
- Las llaves foráneas usan `ON DELETE RESTRICT` salvo que se documente lo
  contrario (para no perder historial de ventas/compras al borrar un
  producto o cliente).
