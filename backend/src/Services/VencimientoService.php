<?php

namespace Retail\Services;

use DateTimeImmutable;
use Exception;
use Retail\Repositories\LoteRepository;

class VencimientoService
{
    public const DIAS_ALERTA = 30;

    private const TRAMOS_DESCUENTO = [
        [7, 20],
        [15, 15],
        [30, 10],
    ];

    private LoteRepository $lotes;

    public function __construct(?LoteRepository $lotes = null)
    {
        $this->lotes = $lotes ?? new LoteRepository();
    }

    public static function diasRestantes(?string $fecha, ?string $hoy = null): ?int
    {
        $fechaDt = self::parsearFecha($fecha);
        $hoyDt = self::parsearFecha($hoy ?? date('Y-m-d'));
        if (!$fechaDt || !$hoyDt) {
            return null;
        }

        return (int) $hoyDt->diff($fechaDt)->format('%r%a');
    }

    /** RN-001: alerta lotes vencidos o con hasta 30 días de vigencia. */
    public static function debeAlertar(?string $fecha, ?string $hoy = null): bool
    {
        $dias = self::diasRestantes($fecha, $hoy);
        return $dias !== null && $dias <= self::DIAS_ALERTA;
    }

    public static function colorAlerta(?string $fecha, ?string $hoy = null): ?string
    {
        $dias = self::diasRestantes($fecha, $hoy);
        if ($dias === null || $dias > self::DIAS_ALERTA) {
            return null;
        }
        return $dias <= 7 ? 'rojo' : 'amarillo';
    }

    public static function descuentoSugerido(?string $fecha, ?string $hoy = null): int
    {
        $dias = self::diasRestantes($fecha, $hoy);
        if ($dias === null || $dias < 0) {
            return 0;
        }

        foreach (self::TRAMOS_DESCUENTO as [$maximo, $porcentaje]) {
            if ($dias <= $maximo) {
                return $porcentaje;
            }
        }
        return 0;
    }

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

    public static function decorarLote(array $lote, ?string $hoy = null): array
    {
        $fecha = $lote['fecha_vencimiento'] ?? null;
        $precio = (float) ($lote['precio'] ?? 0);
        $actual = (int) ($lote['descuento_porcentaje'] ?? 0);
        $lote['dias_restantes'] = self::diasRestantes($fecha, $hoy);
        $lote['nivel_vencimiento'] = self::nivel($fecha, $hoy);
        $lote['color_alerta'] = self::colorAlerta($fecha, $hoy);
        $lote['descuento_sugerido'] = self::descuentoSugerido($fecha, $hoy);
        $lote['precio_con_descuento'] = round($precio * (1 - $actual / 100), 2);
        return $lote;
    }

    public function listarProximosAVencer(?string $hoy = null): array
    {
        $fechaBase = self::parsearFecha($hoy ?? date('Y-m-d'));
        if (!$fechaBase) {
            throw new Exception('La fecha de referencia no es válida', 422);
        }
        $limite = $fechaBase->modify('+' . self::DIAS_ALERTA . ' days')->format('Y-m-d');
        $lotes = array_map(
            static fn(array $lote): array => self::decorarLote($lote, $fechaBase->format('Y-m-d')),
            $this->lotes->findProximosAVencer($limite)
        );

        $resumen = ['dias_alerta' => self::DIAS_ALERTA, 'total' => count($lotes), 'vencidos' => 0, 'criticos' => 0, 'proximos' => 0];
        foreach ($lotes as $lote) {
            if ($lote['nivel_vencimiento'] === 'vencido') {
                $resumen['vencidos']++;
            } elseif (in_array($lote['nivel_vencimiento'], ['critico', 'vence_hoy'], true)) {
                $resumen['criticos']++;
            } else {
                $resumen['proximos']++;
            }
        }
        return ['resumen' => $resumen, 'lotes' => $lotes];
    }

    public function crearLote(array $data): array
    {
        $productoId = filter_var($data['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $stock = filter_var($data['stock'] ?? null, FILTER_VALIDATE_INT);
        $codigo = trim((string) ($data['codigo_lote'] ?? ''));
        $fecha = trim((string) ($data['fecha_vencimiento'] ?? ''));

        if (!$productoId || $productoId < 1) {
            throw new Exception('Selecciona un producto válido', 422);
        }
        if ($stock === false || $stock < 1) {
            throw new Exception('La cantidad del lote debe ser mayor que cero', 422);
        }
        if ($codigo === '' || strlen($codigo) > 64) {
            throw new Exception('El código del lote es obligatorio y admite hasta 64 caracteres', 422);
        }
        if (!self::parsearFecha($fecha)) {
            throw new Exception('La fecha de vencimiento no es válida', 422);
        }

        $id = $this->lotes->create($productoId, $codigo, $stock, substr($fecha, 0, 10));
        return $this->obtenerLote($id);
    }

    public function aplicarDescuentoAutomatico(int $id, ?string $hoy = null): array
    {
        $lote = $this->lotes->findById($id);
        if (!$lote) {
            throw new Exception('Lote no encontrado', 404);
        }
        if (!self::debeAlertar($lote['fecha_vencimiento'], $hoy)) {
            throw new Exception('El lote no vence dentro de los próximos 30 días', 422);
        }
        $porcentaje = self::descuentoSugerido($lote['fecha_vencimiento'], $hoy);
        if ($porcentaje === 0) {
            throw new Exception('No se puede aplicar descuento automático a un lote vencido', 422);
        }
        $this->lotes->aplicarDescuento($id, $porcentaje);
        return $this->obtenerLote($id);
    }

    private function obtenerLote(int $id): array
    {
        $lote = $this->lotes->findById($id);
        if (!$lote) {
            throw new Exception('Lote no encontrado', 404);
        }
        return self::decorarLote($lote);
    }

    private static function parsearFecha(?string $fecha): ?DateTimeImmutable
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }
        $dia = substr(trim($fecha), 0, 10);
        $fechaDt = DateTimeImmutable::createFromFormat('!Y-m-d', $dia);
        $errores = DateTimeImmutable::getLastErrors();
        if (!$fechaDt || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
            return null;
        }
        return $fechaDt->format('Y-m-d') === $dia ? $fechaDt : null;
    }
}
