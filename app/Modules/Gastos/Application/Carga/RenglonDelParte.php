<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Domain\EtiquetaDocumento;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Lo propio de un renglón del parte de horas (DU-C02, RN-GAS-52, 53): el concepto es el de la cabecera ("Horas
 * 1 al 15-oct" si viene vacío), el empleado es obligatorio y las horas van con hasta 2 decimales.
 */
final class RenglonDelParte
{
    public const ESCALA_HORAS = 2;

    public const SIN_EMPLEADO = 'Escribí el empleado.';

    public const SIN_HORAS = 'Poné las horas.';

    public const DECIMALES_HORAS = 'Hasta 2 decimales en las horas.';

    public const SIN_VALOR_HORA = 'Poné el valor hora.';

    private const CONCEPTO = 'Horas ';

    public static function concepto(DatosGasto $datos): string
    {
        if ($datos->concepto !== null) {
            return $datos->concepto;
        }

        return rtrim(self::CONCEPTO.EtiquetaDocumento::periodo(self::dia($datos->periodoDesde), self::dia($datos->periodoHasta)));
    }

    /** Sin espacios al borde ni dobles; vacío = error en el renglón. */
    public static function empleado(DatosRenglon $renglon, Errores $errores): ?string
    {
        $nombre = $renglon->empleado === null ? '' : (string) preg_replace('/\s+/u', ' ', trim($renglon->empleado));

        if ($nombre === '') {
            $errores->renglon($renglon->posicion, 'empleado', self::SIN_EMPLEADO);

            return null;
        }

        return $nombre;
    }

    /**
     * @param  numeric-string  $horas  escala 3
     */
    public static function horas(int $posicion, string $horas, Errores $errores): void
    {
        if (bccomp($horas, '0', TotalDeRenglon::ESCALA_CANTIDAD) <= 0) {
            $errores->renglon($posicion, 'cantidad', self::SIN_HORAS);
        } elseif (bccomp($horas, bcadd($horas, '0', self::ESCALA_HORAS), TotalDeRenglon::ESCALA_CANTIDAD) !== 0) {
            $errores->renglon($posicion, 'cantidad', self::DECIMALES_HORAS);
        }
    }

    private static function dia(?string $fecha): ?CarbonImmutable
    {
        if ($fecha === null) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $fecha) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
