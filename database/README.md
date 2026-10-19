# database/

- `schema.sql` — Estructura completa de la base de datos (tablas, llaves
  foráneas, índices). Se implementa en la **Etapa 3**.
- `seed.sql` — Datos iniciales de prueba (ficticios, sin contraseñas ni datos
  reales). Se implementa en la **Etapa 3**.

## Cómo aplicar los scripts

```bash
mysql -u root -p < schema.sql
mysql -u root -p < seed.sql
```

## Convenciones usadas (a partir de la Etapa 3)

- Nombres de tabla en `snake_case`, plural (`productos`, `detalle_venta`).
- Toda tabla tiene `id` autoincremental como llave primaria.
- Toda fecha de auditoría usa `TIMESTAMP` con `DEFAULT CURRENT_TIMESTAMP`.
- Las llaves foráneas usan `ON DELETE RESTRICT` salvo que se documente lo
  contrario (para no perder historial de ventas/compras al borrar un
  producto o cliente).
