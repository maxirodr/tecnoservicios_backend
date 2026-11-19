<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Pedidos\Application\Eventos\ArmaEvento;
use App\Modules\Pedidos\Application\Eventos\Avisados;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Events\PedidoAsignado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\AdjuntosService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * F-24 / HU-026: alta del pedido (RN-PED-01…10). Nace No iniciada, el solicitante es quien carga, la
 * prioridad la calcula el sistema (o la corrige quien carga, RN-PED-12) y dispara `PedidoAsignado`
 * después del commit. Pedido, seguidores, adjuntos y actividad van en una sola transacción.
 */
final readonly class CrearPedidoAction
{
    public const ACCION = 'creado';

    public function __construct(
        private Connection $db,
        private ObrasConsulta $obras,
        private Asignables $asignables,
        private CalculoDePrioridad $prioridad,
        private AdjuntosService $adjuntos,
        private RegistroActividad $actividad,
        private ArmaEvento $evento,
        private Avisados $avisados,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, DatosPedido $datos): PedidoConAvisados
    {
        ReglasDeFechas::validar($datos->fechaPedido, $datos->periodoInicio, $datos->periodoFin);

        if ($datos->obraId !== null && ! $this->obras->existe($datos->obraId)) {
            throw ValidationException::withMessages(['obra_id' => ['Elegí la obra o \'Sin obra\'.']]);
        }

        $sector = $this->asignables->sector($datos->sectorId);
        $propietario = $this->asignables->persona($datos->propietarioId);

        if ($propietario->sector_id !== $sector->id) {
            throw ValidationException::withMessages(['propietario_id' => ["{$propietario->nombre} no es de {$sector->nombre}."]]);
        }

        $seguidores = SeguidoresAlCrear::validar($datos->seguidores, $actor->id, $propietario->id);
        $fechaPedido = $datos->fechaPedido ?? FechaEmpresa::hoyTexto();
        $calculada = $this->prioridad->calculada(CarbonImmutable::parse($fechaPedido), CarbonImmutable::parse($datos->fechaEntrega));
        $manual = $datos->prioridad !== null && $datos->prioridad !== $calculada;

        $pedido = $this->db->transaction(function () use ($actor, $datos, $propietario, $seguidores, $fechaPedido, $calculada, $manual): Pedido {
            $pedido = Pedido::query()->create([
                'obra_id' => $datos->obraId,
                'tarea' => $datos->tarea,
                'sector_id' => $propietario->sector_id,
                'propietario_id' => $propietario->id,
                'solicitante_id' => $actor->id,
                'con_quien' => $datos->conQuien,
                'fecha_pedido' => $fechaPedido,
                'fecha_entrega' => $datos->fechaEntrega,
                'periodo_inicio' => $datos->periodoInicio,
                'periodo_fin' => $datos->periodoFin,
                'aclaraciones' => $datos->aclaraciones,
                'prioridad' => $manual ? $datos->prioridad : $calculada,
                'prioridad_manual' => $manual,
                'estado' => PedidoEstado::NoIniciada,
            ]);

            $pedido->seguidores()->attach($seguidores);
            $actividad = $this->actividad->registrar($pedido, self::ACCION);
            $this->adjuntos->asociar($actor->id, $datos->adjuntos, $pedido, Pedido::CONTEXTO_ADJUNTOS);

            $this->eventos->dispatch(new PedidoAsignado(
                $pedido->id, $actor->id, $actividad->id, $this->evento->participantes($pedido), $this->evento->foto($pedido),
            ));

            return $pedido;
        });

        return new PedidoConAvisados($pedido, $this->avisados->de($this->evento->participantes($pedido), $actor->id));
    }
}
