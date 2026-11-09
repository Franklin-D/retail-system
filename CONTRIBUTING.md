# Contribuir a retail-system

Gracias por tu interés en el proyecto. Este documento resume el flujo de
trabajo, las convenciones y los criterios para aceptar cambios.

## Requisitos

- PHP 8.1+ (probado con PHP 8.2 de XAMPP).
- MySQL 8.0+.
- Git.
- Node.js 20+ (opcional, para validar sintaxis JavaScript).
- Composer (opcional; el backend incluye un autoload de respaldo).

## Configuración local

1. Clonar el repositorio.
2. Copiar `.env.example` a `.env` y ajustar las credenciales de MySQL.
3. Cargar la base de datos:

   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```

4. Servir el backend:

   ```bash
   php -S localhost:8000 -t backend/public backend/public/router.php
   ```

5. Servir el frontend con Apache/XAMPP o con un servidor estático.

## Estructura del proyecto

Antes de hacer cambios, revisa `README.md` y `docs/architecture.md`. En resumen:

- `backend/src/` contiene las capas `Routes`, `Controllers`, `Services`,
  `Repositories`, `Models`, `Middleware`, `Config` y `Utils`.
- `frontend/features/<modulo>/` contiene el HTML y JS de cada módulo.
- `frontend/shared/` contiene código reutilizable (`api.js`, `auth.js`,
  `helpers.js`, componentes y `toast`).
- `database/` contiene el esquema, los datos de prueba y las migraciones.

## Flujo de ramas

Ver `docs/git-workflow.md`.

- `main`: versión estable.
- `develop`: integración de cambios en curso.
- `feature/*`: una rama por funcionalidad, corrección o etapa.

Crea la rama desde `develop` y abre un Pull Request de vuelta a `develop`.
Los cambios a `main` se integran cuando `develop` esté estable.

## Commits

Usa mensajes en español, en modo imperativo y con prefijo tipo Conventional
Commits:

```text
feat: agrega endpoint POST /api/products
fix: corrige descuento de stock en checkout
refactor: mueve el backend a backend/src con PSR-4
docs: actualiza la estructura en el README
chore: agrega workflow de CI
```

Cada commit debe ser pequeño y con un objetivo claro.

## Pull Requests

- Describe qué cambia y por qué.
- Enlaza el issue relacionado (`Closes #123`).
- Indica cómo se probó el cambio.
- Mantén el PR enfocado en un solo objetivo.
- Completa el checklist de la plantilla.
- No incluyas secretos, credenciales ni archivos `.env`.

## Estilo de código

- PHP: PSR-12, namespaces PSR-4 (`Retail\` → `backend/src/`), consultas
  preparadas con PDO.
- JavaScript: módulos ES, imports relativos y funciones pequeñas.
- No introducir frameworks nuevos sin discutirlo antes.
- Los nombres de dominio se mantienen en español (productos, ventas, etc.).
- Comentarios cortos y solo cuando aportan contexto.

## Reportes de seguridad

No abras un issue público para vulnerabilidades. Sigue el proceso descrito en
`SECURITY.md`.

## Código de conducta

Al participar aceptas el `CODE_OF_CONDUCT.md`.
