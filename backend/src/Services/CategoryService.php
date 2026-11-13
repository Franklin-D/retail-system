<?php

namespace Retail\Services;

use Retail\Repositories\CategoryRepository;

class CategoryService
{
    private CategoryRepository $repo;

    public function __construct()
    {
        $this->repo = new CategoryRepository();
    }

    public function listar(): array
    {
        return $this->repo->findAll();
    }
}
