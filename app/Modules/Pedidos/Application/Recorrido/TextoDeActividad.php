<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Recorrido;

use App\Modules\Pedidos\Application\Comentarios\ComentarPedidoAction;
use App\Modules\Pedidos\Application\Estados\BloquearPedidoAction;
use App\Modules\Pedidos\Application\Estados\CompletarPedidoAction;
use App\Modules\Pedidos\Application\Estados\ReabrirPedidoAction;
use App\Modules\Pedidos\Application\Estados\ReasignarPedidoAction;
use App\Modules\Pedidos\Application\Estados\RetomarPedidoAction;
use App\Modules\Pedidos\Application\Estados\TomarPedidoAction;
use App\Modules\Pedidos\Application\Pedidos\ActualizarPedidoAction;
use App\Modules\Pedidos\Application\Pedidos\CrearPedidoAction;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Shared\Actividad\Actividad;
use App\Shared\Adjuntos\AdjuntosService;

/**
 * Una línea del recorrido de W-09 (RN-PED-31) con quién: "Carla lo tomó → En curso", "Carla lo completó
 * sin tomarlo: 'Entregado en obra'", "Carla lo reasignó de Carla a Diego". Y el "último movimiento" corto
 * de Pedidos que pedí (RN-PED-39).
 */
final class TextoDeActividad
{
    private const LARGO_ULTIMO = 80;

    /**
     * @return list<string>
     */
    public static function lineas(Actividad $actividad): array
    {
        $quien = $actividad->user->nombre ?? 'Sistema';
        $c = $actividad->cambios ?? [];

        return match ($actividad->accion) {
            CrearPedidoAction::ACCION => ["{$quien} creó el pedido"],
            TomarPedidoAction::ACCION => ["{$quien} lo tomó → En curso"],
            BloquearPedidoAction::ACCION => ["{$quien} lo bloqueó: '".self::texto($c['motivo_bloqueo']['despues'] ?? null)."'"],
            RetomarPedidoAction::ACCION => ["{$quien} lo retomó → En curso"],
            CompletarPedidoAction::ACCION => [self::completado($quien, $c)],
            ReabrirPedidoAction::ACCION => ["{$quien} lo reabrió (falta algo): '".self::texto($c['comentario']['despues'] ?? null)."'"],
            ReasignarPedidoAction::ACCION => [self::reasignado($quien, $c)],
            ComentarPedidoAction::ACCION => ["{$quien} comentó: '".self::texto($c['comentario']['despues'] ?? null)."'"],
            ActualizarPedidoAction::ACCION => array_map(static fn (string $l): string => "{$quien} editó · {$l}", TextoDeCambios::edicion($c)),
            AdjuntosService::ACCION_AGREGADO => array_map(static fn (string $l): string => "{$quien} · {$l}", TextoDeCambios::adjuntos($c, 'agregado')),
            AdjuntosService::ACCION_QUITADO => array_map(static fn (string $l): string => "{$quien} · {$l}", TextoDeCambios::adjuntos($c, 'quitado')),
            default => ["{$quien}: {$actividad->accion}"],
        };
    }

    /** "15-oct · Carla: 'Pedido hecho…'" sin la fecha (la pone el front con `cuando`). */
    public static function ultimoMovimiento(Actividad $actividad, int $comprobantes): string
    {
        $quien = $actividad->user->nombre ?? 'Sistema';
        $c = $actividad->cambios ?? [];

        return match ($actividad->accion) {
            CrearPedidoAction::ACCION => 'creado; nadie lo tomó todavía',
            ComentarPedidoAction::ACCION => "{$quien}: '".mb_strimwidth(self::texto($c['comentario']['despues'] ?? null), 0, self::LARGO_ULTIMO, '…')."'",
            CompletarPedidoAction::ACCION => $comprobantes > 0 ? 'completado con comprobante' : 'completado',
            default => self::lineas($actividad)[0] ?? $quien,
        };
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     */
    private static function completado(string $quien, array $c): string
    {
        $sinTomar = ($c['estado']['antes'] ?? null) === PedidoEstado::NoIniciada->value ? ' sin tomarlo' : '';

        return "{$quien} lo completó{$sinTomar}: '".self::texto($c['comentario_cierre']['despues'] ?? null)."'";
    }

    /**
     * @param  array<string, array{antes: mixed, despues: mixed}>  $c
     */
    private static function reasignado(string $quien, array $c): string
    {
        $linea = "{$quien} lo reasignó de ".self::texto($c['propietario']['antes'] ?? null).' a '.self::texto($c['propietario']['despues'] ?? null);

        if (isset($c['estado'])) {
            $linea .= ' → '.(PedidoEstado::tryFrom(self::texto($c['estado']['despues']))?->etiqueta() ?? '');
        }

        return isset($c['comentario']) ? $linea.": '".self::texto($c['comentario']['despues'])."'" : $linea;
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
