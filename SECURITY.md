# Política de seguridad

## Versiones soportadas

| Versión | Soporte |
| --- | --- |
| `main` | Sí |
| `develop` | Solo para pruebas |

## Reportar una vulnerabilidad

**No abras un issue público para reportar vulnerabilidades.**

Usa una de estas vías:

1. Abre un **Security Advisory privado** en GitHub:
   `Security → Advisories → Report a vulnerability`.
2. Escribe a **franklinanahua5@gmail.com**.

Incluye, si es posible:

- Descripción de la vulnerabilidad y su impacto.
- Pasos para reproducirla.
- Versión, rama o commit afectado.
- Entorno (sistema operativo, PHP, MySQL, navegador).
- Posible mitigación o corrección sugerida.

## Tiempos de respuesta

- Acuse de recibo: hasta 72 horas.
- Evaluación inicial: hasta 7 días.
- Corrección: según la gravedad y complejidad del caso.

## Alcance

Se consideran dentro del alcance, entre otros:

- Autenticación y manejo de sesiones.
- Inyección SQL y consultas no preparadas.
- Validación y almacenamiento de archivos subidos.
- Exposición de credenciales o datos sensibles.
- Control de acceso a endpoints de la API.

Fuera del alcance:

- Configuraciones locales de XAMPP/Apache no incluidas en el repositorio.
- Vulnerabilidades de dependencias de terceros sin impacto directo.
- Ataques de denegación de servicio.

## Buenas prácticas del proyecto

- No subir archivos `.env` ni secretos al repositorio.
- Usar consultas preparadas con PDO.
- Validar el tipo real de los archivos subidos.
- Mantener las dependencias y el entorno actualizados.
