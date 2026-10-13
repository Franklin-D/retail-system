<?php
/**
 * middleware/validate.middleware.php
 * -----------------------------------------------------------------------
 * Responsabilidad: parsear el body JSON de la petición hacia un array
 * asociativo PHP, disponible para los controllers.
 *
 * Implementación real: ETAPA 5.
 * -----------------------------------------------------------------------
 */

function getJsonBody(): array
{
    // TODO (Etapa 5):
    // $raw = file_get_contents('php://input');
    // $data = json_decode($raw, true);
    // return is_array($data) ? $data : [];
    return [];
}
