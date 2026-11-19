<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Recordatorio;

use App\Modules\Avisos\Application\EventoAvisado;
use App\Modules\Avisos\Application\PersonasDelPedido;
use App\Modules\Avisos\Application\RegistrarAvisos;
use App\Modules\Avisos\Domain\ContextoDelAviso;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Pedidos\Contracts\PedidosParaRecordar;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * "Vence mañana" (F-37, RN-AVI-16…18): desde las 8:00 de Argentina, cada corrida horaria busca los pedidos
 * abiertos con entrega mañana (días corridos: un lunes se recuerda el domingo, DU-Q01) y avisa al propietario
 * o a las personas activas del sector, solo en el sistema. No se repite: la clave lleva pedido, fecha de
 * entrega y persona; si cambian la entrega, sale otro para la fecha nueva.
 */
final class RecordarEntregasDeMananaAction
{
    /** Desde esta hora (Argentina) sale el recordatorio del día (resuelta DU-P21). */
    public const HORA_DESDE = 8;

    public function __construct(
        private readonly PedidosParaRecordar $pedidos,
        private readonly PersonasDelPedido $personas,
        private readonly DestinatariosPorEvento $reglas,
        private readonly RegistrarAvisos $registrar,
    ) {}

    /**
     * @return int|null avisos creados en esta corrida; null = todavía no son las 8:00
     */
    public function handle(DateTimeInterface $ahora): ?int
    {
        $local = CarbonImmutable::instance($ahora)->setTimezone(config()->string('app.zona_empresa'));
        if ($local->hour < self::HORA_DESDE) {
            return null;
        }

        $manana = $local->startOfDay()->addDay();
        $creados = 0;

        foreach ($this->pedidos->conEntregaEl($manana) as $pedido) {
            $destinatarios = $this->reglas->recordatorio(
                $pedido->propietarioId,
                $this->personas->padronDelRecordatorio($pedido->propietarioId, $pedido->sectorId),
            );

            $creados += $this->registrar->handle(
                EventoAvisado::recordatorio($pedido->pedidoId, $pedido->fechaEntrega, new ContextoDelAviso($pedido->tarea, $pedido->obraNombre)),
                $destinatarios,
            );
        }

        return $creados;
    }
}
