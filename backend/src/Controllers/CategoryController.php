<?php

namespace Retail\Controllers;

use Retail\Services\CategoryService;
use Retail\Utils\Response;

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
