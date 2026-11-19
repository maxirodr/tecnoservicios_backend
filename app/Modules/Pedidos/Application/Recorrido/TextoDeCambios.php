<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Recorrido;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Application\Pedidos\ActualizarPedidoAction;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Adjuntos\AdjuntosService;
use Carbon\CarbonImmutable;

/**
 * "Qué cambió" de Editar (W-32, RN-PED-29) en palabras del cliente: "Fecha de entrega: 21-10 → 23-10 (la
 * prioridad sigue Normal: 9 → 11 días)", "Tarea: 'Alquiler de hidro' → 'Alquiler de hidrolavadora'",
 * "Agregó link: ficha técnica". Los adjuntos van por nombre, sin URL ni ruta.
 */
final class TextoDeCambios
{
    private const ETIQUETAS = [
        'tarea' => 'Tarea',
        'obra' => 'Obra',
        'fecha_pedido' => 'Fecha de pedido',
        'fecha_entrega' => 'Fecha de entrega',
        'periodo_inicio' => 'Inicio del período',
        'periodo_fin' => 'Fin del período',
        'con_quien' => 'Con quién / para quién',
        'aclaraciones' => 'Aclaraciones',
        'prioridad' => 'Prioridad',
    ];

    private const CON_COMILLAS = ['tarea', 'con_quien', 'aclaraciones'];

    private const FECHAS = ['fecha_pedido', 'fecha_entrega', 'periodo_inicio', 'periodo_fin'];

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @return list<string>
     */
    public static function edicion(array $cambios): array
    {
        $lineas = [];
        $dias = $cambios[ActualizarPedidoAction::DIAS] ?? null;
        $nota = $dias === null ? '' : self::notaDePrioridad($cambios, $dias);

        foreach ($cambios as $campo => $cambio) {
            // Con una fecha cambiada, la prioridad va como nota de esa línea y no como línea aparte.
            if (! isset(self::ETIQUETAS[$campo]) || ($campo === 'prioridad' && $dias !== null)) {
                continue;
            }

            $linea = self::ETIQUETAS[$campo].': '.self::valor($campo, $cambio['antes']).' → '.self::valor($campo, $cambio['despues']);

            if (in_array($campo, ['fecha_pedido', 'fecha_entrega'], true)) {
                $linea .= $nota;
                $nota = '';
            }

            $lineas[] = $linea;
        }

        return $lineas;
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @param  'agregado'|'quitado'  $accion
     * @return list<string>
     */
    public static function adjuntos(array $cambios, string $accion, ?string $tipoActual = null): array
    {
        $lineas = [];

        foreach ($cambios as $contexto => $cambio) {
            $nombre = self::texto($accion === 'agregado' ? $cambio['despues'] : $cambio['antes']);
            $que = $contexto === Pedido::CONTEXTO_COMPROBANTES ? 'comprobante' : ($tipoActual ?? 'link o archivo');
            $lineas[] = ($accion === 'agregado' ? 'Agregó ' : 'Quitó ')."{$que}: {$nombre}";
        }

        return $lineas;
    }

    public static function esAdjunto(string $accion): bool
    {
        return in_array($accion, [AdjuntosService::ACCION_AGREGADO, AdjuntosService::ACCION_QUITADO], true);
    }

    public static function prioridad(mixed $valor): string
    {
        $texto = self::texto($valor);
        $partes = explode(' ', $texto, 2);
        $prioridad = Prioridad::tryFrom($partes[0]);

        return $prioridad === null ? $texto : trim(ucfirst($prioridad->value).' '.($partes[1] ?? ''));
    }

    /** "21-10" */
    public static function fechaCorta(mixed $valor): string
    {
        $texto = self::texto($valor);

        return $texto === '' ? '(vacío)' : CarbonImmutable::parse($texto)->format('d-m');
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $cambios
     * @param  array{antes: mixed, despues: mixed}  $dias
     */
    private static function notaDePrioridad(array $cambios, array $dias): string
    {
        if (isset($cambios['prioridad'])) {
            return ' (la prioridad pasó de '.self::prioridad($cambios['prioridad']['antes']).' a '.self::prioridad($cambios['prioridad']['despues']).')';
        }

        $sigue = self::prioridad($cambios[ActualizarPedidoAction::PRIORIDAD_SIGUE]['despues'] ?? null);

        return " (la prioridad sigue {$sigue}: ".self::texto($dias['antes']).' → '.self::texto($dias['despues']).' días)';
    }

    private static function valor(string $campo, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return $campo === 'obra' ? 'Sin obra' : '(vacío)';
        }

        return match (true) {
            in_array($campo, self::FECHAS, true) => self::fechaCorta($valor),
            $campo === 'prioridad' => self::prioridad($valor),
            in_array($campo, self::CON_COMILLAS, true) => "'".self::texto($valor)."'",
            default => self::texto($valor),
        };
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
