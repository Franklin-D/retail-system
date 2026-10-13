<?php
/**
 * controllers/SupplierController.php
 * -----------------------------------------------------------------------
 * Traduce HTTP <-> PHP para este recurso: lee input (query/body), invoca
 * al service correspondiente y arma la respuesta JSON con Response.php.
 * No contiene SQL ni reglas de negocio.
 *
 * Implementación real: Etapa 8
 * -----------------------------------------------------------------------
 */

class SupplierController
{
    // TODO (Etapa 8): constructor recibe el Service correspondiente
    // (inyección simple, sin contenedor DI).

    public function index(): void
    {
        // TODO: listar recurso (con filtros opcionales por query string)
    }

    public function show(int $id): void
    {
        // TODO: obtener un registro por id
    }

    public function store(): void
    {
        // TODO: validar payload y crear registro
    }

    public function update(int $id): void
    {
        // TODO: validar payload y actualizar registro
    }

    public function destroy(int $id): void
    {
        // TODO: eliminar/desactivar registro
    }
}
