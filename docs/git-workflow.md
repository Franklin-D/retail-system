# Estrategia de ramas (Git)

Estrategia simple, apropiada para un equipo pequeño de curso universitario:

```
main         → siempre desplegable / estable (solo se hace merge desde develop)
develop      → integración de features en curso
feature/*    → una rama por etapa o por módulo, ej:
                 feature/schema-sql
                 feature/auth-backend
                 feature/products-crud
                 feature/frontend-sales
```

## Flujo de trabajo

1. Crear rama desde `develop`: `git checkout -b feature/nombre-etapa`
2. Trabajar y commitear en español, en modo imperativo: `git commit -m "Agrega CRUD de productos"`
3. Al terminar la etapa: Pull Request de `feature/*` → `develop`
4. Cuando `develop` esté estable (todas las etapas de un bloque funcionando): PR de `develop` → `main`

## Convención de commits sugerida

```
feat: nueva funcionalidad
fix: corrección de error
docs: cambios en documentación
refactor: cambio interno sin alterar comportamiento
chore: tareas de mantenimiento (configuración, dependencias)
```

Ejemplo: `feat: agrega endpoint POST /api/products`
