<?php
require_once __DIR__ . '/../controllers/ProductController.php';

$controller = new ProductController();

if (($rest[0] ?? '') === 'drafts') {
    // Pre-carga de productos (borradores, ocultos en tienda):
    //   GET    /products/drafts               lista borradores
    //   POST   /products/drafts               crea borrador (FormData con imagen)
    //   GET    /products/drafts/{id}          un borrador
    //   POST   /products/drafts/{id}          edita borrador (FormData)
    //   POST   /products/drafts/{id}/publish  "dar de alta": pasa a publicado
    //   DELETE /products/drafts/{id}          descarta borrador
    $tieneId  = isset($rest[1]);
    $draftId  = $tieneId && ctype_digit((string) $rest[1]) ? (int) $rest[1] : null;
    $accion   = $rest[2] ?? null;

    if (!$tieneId && $method === 'GET') {
        $controller->drafts();
    } elseif (!$tieneId && $method === 'POST') {
        $controller->storeDraft();
    } elseif ($draftId !== null && $accion === null && $method === 'GET') {
        $controller->showDraft($draftId);
    } elseif ($draftId !== null && $accion === null && ($method === 'POST' || $method === 'PUT')) {
        $controller->updateDraft($draftId);
    } elseif ($draftId !== null && $accion === 'publish' && $method === 'POST') {
        $controller->publishDraft($draftId);
    } elseif ($draftId !== null && $accion === null && $method === 'DELETE') {
        $controller->destroyDraft($draftId);
    } else {
        Response::error('Método o parámetro no permitido para /products/drafts', 405);
    }
} else {
    $id = isset($rest[0]) && ctype_digit((string) $rest[0]) ? (int) $rest[0] : null;

    if ($method === 'GET' && $id === null) {
        $controller->index();
    } elseif ($method === 'GET' && $id !== null) {
        $controller->show($id);
    } elseif ($method === 'POST' && $id === null) {
        $controller->store();
    } elseif (($method === 'PUT' || $method === 'POST') && $id !== null) {
        $controller->update($id);
    } elseif ($method === 'DELETE' && $id !== null) {
        $controller->destroy($id);
    } else {
        Response::error('Método o parámetro no permitido para /products', 405);
    }
}
