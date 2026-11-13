# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

### Added

- Autoload PSR-4 mediante `composer.json` y `backend/autoload.php`.
- Archivo `.env.example` y `.gitignore`.
- Estructura de repositorio: `.github/` (plantillas de issues, PR, CODEOWNERS y
  workflow de CI), `.editorconfig`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`,
  `SECURITY.md` y `CHANGELOG.md`.
- Workflow de GitHub Actions para validar sintaxis PHP y JavaScript.
- HU-101: alertas y descuentos automáticos por productos próximos a vencer
  (`GET /api/products/expiring`, tramos 10/15/20 % y descuento aplicado en el
  checkout).

### Changed

- Backend reorganizado en `backend/src/` con namespaces `Retail\`.
- Frontend reorganizado por funcionalidad en `frontend/features/<modulo>/`.
- Código compartido del frontend movido a `frontend/shared/`.
- Configuración y middleware convertidos en clases (`Env`, `AuthMiddleware`,
  `CorsMiddleware`).
- README, `docs/architecture.md`, `database/README.md` y `.clinerules`
  actualizados a la nueva estructura.

### Removed

- `backend/config.php` (conexión `mysqli` legacy).
- `backend/middleware/validate.middleware.php` (sin uso).
- `database/retail.sql` (esquema legacy sustituido por `schema.sql`).
- Imágenes duplicadas en `frontend/assets/images/`.
