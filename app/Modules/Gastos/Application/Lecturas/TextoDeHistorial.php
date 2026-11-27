<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Lecturas;

use App\Modules\Gastos\Application\Carga\CargarGastoAction;
use App\Modules\Gastos\Application\Correccion\AnularGastoAction;
use App\Modules\Gastos\Application\Correccion\EscribirCorreccion;
use App\Modules\Gastos\Application\Pagos\CambiarPagoAction;
use App\Modules\Gastos\Application\Pagos\TextoDelPago;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\AdjuntosService;

/**
 * El historial de W-34 en palabras del cliente (RN-GAS-42): "Diego cargó la factura (5 renglones)",
 * "Diego cambió el rubro de 'Guantes de trabajo': MATERIALES → INSUMOS", "Diego agregó el archivo
 * factura.pdf" (por nombre, sin URL ni ruta), "Diego anuló la factura: 'Cargada dos veces'".
 */
final class TextoDeHistorial
{
    private const CAMPOS_CABECERA = [
        'numero' => 'el número', 'fecha' => 'la fecha', 'proveedor' => 'el proveedor',
        'rendido_por' => 'quién rindió', 'periodo' => 'el período', 'concepto' => 'el concepto',
    ];

    private const CAMPOS_RENGLON = [
        'concepto' => 'el concepto', 'cantidad' => 'la cantidad', 'precio' => 'el precio', 'obra' => 'la obra',
        'rubro' => 'el rubro', 'material' => 'el material', 'detalle' => 'el detalle', 'fecha' => 'la fecha',
        'empleado' => 'el empleado',
    ];

    /** En el parte de horas la cantidad son las horas y el precio, el valor hora (RN-GAS-56). */
    private const CAMPOS_DEL_PARTE = ['cantidad' => 'las horas', 'precio' => 'el valor hora'];

    /**
     * @return list<string>
     */
    public static function lineas(Actividad $actividad, Origen $origen): array
    {
        $quien = $actividad->user->nombre ?? 'El sistema';
        $c = $actividad->cambios ?? [];
        $que = self::queSeCargo($origen);

        $frases = match ($actividad->accion) {
            CargarGastoAction::ACCION => ["cargó {$que}".self::renglones($c)],
            AnularGastoAction::ACCION => ["anuló {$que}: '".self::texto($c['motivo']['despues'] ?? '')."'"],
            EscribirCorreccion::EDITADO => self::cambios($c, self::CAMPOS_CABECERA, null),
            EscribirCorreccion::RENGLON_CAMBIADO => self::cambios($c, $origen === Origen::ParteHoras ? [...self::CAMPOS_RENGLON, ...self::CAMPOS_DEL_PARTE] : self::CAMPOS_RENGLON, self::texto($c['renglon']['antes'] ?? '')),
            CambiarPagoAction::PAGADA => [TextoDelPago::pagada($c)],
            CambiarPagoAction::IMPAGADA => ['marcó la factura como impaga'],
            EscribirCorreccion::RENGLON_AGREGADO => ['agregó el renglón '.self::texto($c['renglon']['despues'] ?? '')],
            EscribirCorreccion::RENGLON_QUITADO => ["quitó el renglón '".self::texto($c['renglon']['antes'] ?? '')."'"],
            AdjuntosService::ACCION_AGREGADO => self::adjuntos($c, 'agregó', 'despues'),
            AdjuntosService::ACCION_QUITADO => self::adjuntos($c, 'quitó', 'antes'),
            default => [],
        };

        $lineas = array_map(static fn (string $frase): string => "{$quien} {$frase}", $frases);

        // RN-IMP-17: de dónde salió cada número de la carga inicial.
        if ($actividad->accion === CargarGastoAction::ACCION && isset($c['equivalencias'])) {
            $lineas[] = self::texto($c['equivalencias']['despues']);
        }

        return $lineas;
    }

    public static function queSeCargo(Origen $origen): string
    {
        return match ($origen) {
            Origen::Factura => 'la factura',
            Origen::SinComprobante, Origen::Combustible, Origen::ConsumoStock => 'el gasto sin documento',
            Origen::RetiroRemito => 'el retiro',
            Origen::Devolucion => 'la devolución',
            Origen::Amortizacion => 'la amortización',
            Origen::MontoPeriodo => 'el período',
            Origen::ParteHoras => 'el parte de horas',
            Origen::SaldoInicial => 'la carga inicial',
        };
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     */
    private static function renglones(array $c): string
    {
        $n = (int) self::texto($c['renglones']['despues'] ?? 0);
        $cuantos = $n === 1 ? '1 renglón' : "{$n} renglones";
        // RN-IMP-39: "cargó la carga inicial (5 renglones, gastado hasta el 30/11/2026)".
        $corte = isset($c['carga_inicial']) ? ', '.self::texto($c['carga_inicial']['despues']) : '';

        return " ({$cuantos}{$corte})";
    }

    /**
     * "cambió el número: A → B" · "cambió el precio de 'Cemento x 50 kg': 9.800 → 9.500 (total 294.000 → 285.000)".
     *
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     * @param  array<string, string>  $campos
     * @return list<string>
     */
    private static function cambios(array $c, array $campos, ?string $concepto): array
    {
        $lineas = [];

        foreach ($campos as $campo => $nombre) {
            if (! isset($c[$campo])) {
                continue;
            }

            $de = $concepto === null || $campo === 'concepto' ? '' : " de '{$concepto}'";
            $linea = "cambió {$nombre}{$de}: ".self::valor($c[$campo]['antes']).' → '.self::valor($c[$campo]['despues']);

            if (self::conTotal($c, $campo)) {
                $linea .= ' (total '.self::valor($c['total']['antes']).' → '.self::valor($c['total']['despues']).')';
            }

            $lineas[] = $linea;
        }

        return $lineas;
    }

    /**
     * El total va una vez, junto al precio (o a la cantidad si el precio no cambió), y no cuando es igual
     * al precio (cantidad 1: amortización, períodos).
     *
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     */
    private static function conTotal(array $c, string $campo): bool
    {
        if (! isset($c['total']) || ! ($campo === 'precio' || ($campo === 'cantidad' && ! isset($c['precio'])))) {
            return false;
        }

        return ! isset($c['precio']) || $c['precio'] !== $c['total'];
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     * @return list<string>
     */
    private static function adjuntos(array $c, string $verbo, string $lado): array
    {
        return array_values(array_map(
            static fn (array $cambio): string => "{$verbo} el archivo ".self::texto($cambio[$lado]),
            $c,
        ));
    }

    private static function valor(mixed $valor): string
    {
        return $valor === null || $valor === '' ? '—' : self::texto($valor);
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
