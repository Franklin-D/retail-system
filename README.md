# retail-system

Sistema de gestión retail (Planet Color): inventario, ventas, compras,
facturación, finanzas y reportes.

- **Backend:** PHP 8.1+ con PDO/MySQL, organizado en capas
  (`Routes → Controllers → Services → Repositories`) y autoload PSR-4.
- **Frontend:** HTML5 + CSS3 + JavaScript vanilla (módulos ES), sin frameworks.
- **Base de datos:** MySQL (scripts versionados en `database/`).

## Estructura

```
retail-system/
├── composer.json              # Autoload PSR-4: Retail\ -> backend/src/
├── .env.example               # Plantilla de variables de entorno
├── backend/
│   ├── autoload.php           # Autoload PSR-4 (fallback sin Composer)
│   ├── public/                # Document root
│   │   ├── index.php          # Front controller
│   │   ├── router.php         # Router para `php -S`
│   │   ├── .htaccess
│   │   └── uploads/           # Imágenes de productos
│   └── src/
│       ├── Config/            # Database (PDO) y Env (.env)
│       ├── Controllers/       # Traducción HTTP <-> PHP
│       ├── Middleware/        # Autenticación y CORS
│       ├── Models/            # Entidades planas
│       ├── Repositories/      # SQL con PDO
│       ├── Routes/            # Despacho método HTTP + URL
│       ├── Services/          # Reglas de negocio
│       └── Utils/             # Response, Request, Validator, JWT
├── frontend/
│   ├── index.html
│   ├── assets/css/            # Estilos globales
│   ├── features/              # Un directorio por módulo (HTML + JS)
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── products/
│   │   ├── drafts/
│   │   ├── sales/
│   │   ├── purchases/
│   │   ├── customers/
│   │   ├── suppliers/
│   │   ├── invoices/
│   │   ├── finance/
│   │   └── reports/
│   └── shared/
│       ├── components/        # Fragmentos HTML (sidebar)
│       └── js/                # api, auth, helpers, modal, toast, load-component
├── database/                  # schema.sql, seed.sql, migraciones
└── docs/                      # Arquitectura, workflow y OpenAPI
```

## Puesta en marcha

1. Copiar `.env.example` a `.env` y ajustar las credenciales de MySQL.
2. Crear la base de datos y cargar los scripts:

   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   ```

3. Servir el backend (desarrollo):

   ```bash
   php -S localhost:8000 -t backend/public backend/public/router.php
   ```

4. Abrir `frontend/index.html` desde un servidor web (Apache/XAMPP o
   `php -S`) para que los módulos ES y `fetch()` funcionen.

En producción, el document root de Apache debe apuntar a `backend/public/`.
El frontend se sirve como archivos estáticos.

## Autoload

Las clases viven en el namespace `Retail\` y se autocargan desde `backend/src/`.
Si Composer está disponible:

```bash
composer dump-autoload
```

Sin Composer, `backend/autoload.php` registra un autoloader PSR-4 equivalente.
