<?php
require_once __DIR__ . '/../repositories/CategoryRepository.php';

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
