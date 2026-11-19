<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application;

use App\Modules\Avisos\Domain\ContextoDelAviso;
use App\Modules\Avisos\Domain\Destinatario;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\EventoDePedido;
use Closure;

/**
 * Caso de uso común de los listeners (RN-AVI-01): arma el padrón con Identidad, decide destinatarios con la
 * regla del evento y registra los avisos con la foto del pedido que trae el evento (`FotoDelPedido`).
 */
final class AvisarEventoDePedido
{
    public function __construct(
        private readonly PersonasDelPedido $personas,
        private readonly DestinatariosPorEvento $reglas,
        private readonly RegistrarAvisos $registrar,
    ) {}

    /**
     * @param  Closure(DestinatariosPorEvento, Padron): list<Destinatario>  $regla
     */
    public function handle(EventoDePedido $evento, Closure $regla, ?string $extracto = null, int $comprobantes = 0, bool $conSuperior = false): int
    {
        $participantes = $evento->participantes;
        $destinatarios = $regla($this->reglas, $this->personas->padron($participantes, $conSuperior));

        if ($destinatarios === []) {
            return 0;
        }

        $foto = $evento->foto;
        $contexto = new ContextoDelAviso(
            tarea: $foto->tarea,
            obraNombre: $foto->obraNombre,
            actorNombre: $this->personas->nombre($evento->actorId),
            extracto: $extracto,
            comprobantes: $comprobantes,
            solicitanteNombre: $this->personas->nombre($participantes->solicitanteId),
            sectorNombre: $this->personas->nombreDelSector($participantes->sectorId),
        );

        return $this->registrar->handle(
            EventoAvisado::deActividad($evento->actividadId, $evento->pedidoId, $evento->actorId, $contexto, $foto->obraId, $foto->fechaEntrega, $foto->prioridad),
            $destinatarios,
        );
    }
}
