<?php
/**
 * repositories/InvoiceRepository.php
 * -----------------------------------------------------------------------
 * Único lugar con SQL para esta entidad (PDO + consultas preparadas).
 * No decide reglas de negocio, solo persiste/consulta.
 *
 * Implementación real: Etapa 9
 * -----------------------------------------------------------------------
 */

class InvoiceRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // TODO (Etapa 9): findAll(), findById($id), create(array $data),
    // update($id, array $data), delete($id) — usando sentencias preparadas.
}
