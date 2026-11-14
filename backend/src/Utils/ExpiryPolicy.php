<?php

namespace Retail\Utils;

use DateTimeImmutable;

/**
 * Reglas de negocio de vencimiento y descuentos sugeridos (HU-101).
 *
 * Tramos de descuento para productos que aún NO están vencidos:
 *   1 a 7 días   -> 20 %
 *   8 a 15 días  -> 15 %
 *   16 a 30 días -> 10 %
 *   más de 30    -> sin descuento
 *
 * Los productos vencidos se marcan como "vencido" y no reciben descuento
 * automático: deben revisarse/retirarse del inventario.
 */
class ExpiryPolicy
{
    public const DIAS_ALERTA = 30;

    /** [días máximos restantes, porcentaje de descuento] */
    private const TRAMOS = [
        [7, 20],
        [15, 15],
        [30, 10],
    ];

    public static function diasRestantes(?string $fecha, ?string $hoy = null): ?int
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        $hoyDt = new DateTimeImmutable($hoy ?? date('Y-m-d'));
        $fechaDt = new DateTimeImmutable(substr($fecha, 0, 10));

        return (int) $hoyDt->diff($fechaDt)->format('%r%a');
    }

    public static function descuentoPorcentaje(?string $fecha, ?string $hoy = null): int
    {
        $dias = self::diasRestantes($fecha, $hoy);
        if ($dias === null || $dias < 0) {
            return 0;
        }

        foreach (self::TRAMOS as [$maximo, $porcentaje]) {
            if ($dias <= $maximo) {
                return $porcentaje;
            }
        }

        return 0;
    }

    /** devuelve 'vencido', 'vence_hoy', 'critico', 'proximo' o null */
    public static function nivel(?string $fecha, ?string $hoy = null): ?string
    {
        $dias = self::diasRestantes($fecha, $hoy);
        if ($dias === null) {
            return null;
        }
        if ($dias < 0) {
            return 'vencido';
        }
        if ($dias === 0) {
            return 'vence_hoy';
        }
        if ($dias <= 7) {
            return 'critico';
        }
        if ($dias <= self::DIAS_ALERTA) {
            return 'proximo';
        }

        return null;
    }

    public static function precioConDescuento(float $precio, ?string $fecha, ?string $hoy = null): float
    {
        $porcentaje = self::descuentoPorcentaje($fecha, $hoy);
        return round($precio * (1 - $porcentaje / 100), 2);
    }

    public static function proximoAVencer(?string $fecha, ?string $hoy = null): bool
    {
        $dias = self::diasRestantes($fecha, $hoy);
        return $dias !== null && $dias >= 0 && $dias <= self::DIAS_ALERTA;
    }

    /** Agrega los campos de alerta/descuento a una fila de producto. */
    public static function decorar(array $producto, ?string $hoy = null): array
    {
        $fecha = $producto['fecha_vencimiento'] ?? null;
        $precio = (float) ($producto['precio'] ?? 0);
        $porcentaje = self::descuentoPorcentaje($fecha, $hoy);
        $precioFinal = round($precio * (1 - $porcentaje / 100), 2);

        $producto['dias_restantes']       = self::diasRestantes($fecha, $hoy);
        $producto['nivel_vencimiento']    = self::nivel($fecha, $hoy);
        $producto['descuento_porcentaje'] = $porcentaje;
        $producto['precio_con_descuento'] = $precioFinal;
        $producto['ahorro']               = round($precio - $precioFinal, 2);

        return $producto;
    }
}
