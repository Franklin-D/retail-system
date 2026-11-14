<?php

require_once dirname(__DIR__) . '/backend/autoload.php';

use Retail\Services\VencimientoService;
use Retail\Utils\ExpiryPolicy;

$hoy = '2026-11-13';
$pruebas = [
    'límite de 30 días incluido' => [true, VencimientoService::debeAlertar('2026-12-13', $hoy)],
    '31 días fuera de alerta' => [false, VencimientoService::debeAlertar('2026-12-14', $hoy)],
    'lote vencido permanece en alerta roja' => ['rojo', VencimientoService::colorAlerta('2026-11-12', $hoy)],
    'lote a 7 días recibe alerta roja' => ['rojo', VencimientoService::colorAlerta('2026-11-20', $hoy)],
    'lote a 8 días recibe alerta amarilla' => ['amarillo', VencimientoService::colorAlerta('2026-11-21', $hoy)],
    '7 días sugiere 20 por ciento' => [20, VencimientoService::descuentoSugerido('2026-11-20', $hoy)],
    '8 días sugiere 15 por ciento' => [15, VencimientoService::descuentoSugerido('2026-11-21', $hoy)],
    '16 días sugiere 10 por ciento' => [10, VencimientoService::descuentoSugerido('2026-11-29', $hoy)],
    '30 días sugiere 10 por ciento' => [10, VencimientoService::descuentoSugerido('2026-12-13', $hoy)],
    'vence hoy sugiere 20 por ciento' => [20, VencimientoService::descuentoSugerido($hoy, $hoy)],
    'lote vencido no recibe descuento automático' => [0, VencimientoService::descuentoSugerido('2026-11-12', $hoy)],
    'fecha ausente no dispara alerta' => [false, VencimientoService::debeAlertar(null, $hoy)],
    'fecha mal formada no dispara alerta' => [false, VencimientoService::debeAlertar('2026-02-31', $hoy)],
    'la política de producto conserva el descuento histórico' => [15, ExpiryPolicy::descuentoPorcentaje('2026-11-21', $hoy)],
    'la política de producto no marca fechas pasadas como próximas' => [false, ExpiryPolicy::proximoAVencer('2026-11-12', $hoy)],
];

$fallos = [];
foreach ($pruebas as $nombre => [$esperado, $obtenido]) {
    if ($esperado !== $obtenido) {
        $fallos[] = sprintf('%s: esperado %s, obtenido %s', $nombre, var_export($esperado, true), var_export($obtenido, true));
    }
}

if ($fallos !== []) {
    fwrite(STDERR, implode(PHP_EOL, $fallos) . PHP_EOL);
    exit(1);
}

printf("OK: %d pruebas de vencimiento aprobadas.%s", count($pruebas), PHP_EOL);
