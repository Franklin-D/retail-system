<?php
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../utils/Response.php';

class CategoryController
{
    private CategoryService $service;

    public function __construct()
    {
        $this->service = new CategoryService();
    }

    public function index(): void
    {
        Response::success($this->service->listar(), 'Listado de categorías');
    }
}
