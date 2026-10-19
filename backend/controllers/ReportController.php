<?php
require_once __DIR__ . '/../services/ReportService.php';
require_once __DIR__ . '/../utils/Response.php';

class ReportController
{
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService();
    }

    public function index(): void
    {
        Response::success($this->service->catalogo(), 'Catálogo de reportes');
    }

    public function summary(): void
    {
        Response::success($this->service->resumen(), 'Resumen obtenido');
    }

    public function inventory(): void
    {
        Response::success($this->service->inventario(), 'Reporte de inventario');
    }

    public function profits(): void
    {
        Response::success($this->service->ganancias(), 'Reporte de ganancias');
    }
}
