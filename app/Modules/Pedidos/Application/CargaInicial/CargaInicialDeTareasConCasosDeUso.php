<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\CargaInicial;

use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Pedidos\Application\Pedidos\Asignables;
use App\Modules\Pedidos\Application\Pedidos\CalculoDePrioridad;
use App\Modules\Pedidos\Application\Pedidos\CrearPedidoAction;
use App\Modules\Pedidos\Application\Pedidos\ReglasDeFechas;
use App\Modules\Pedidos\Contracts\CargaInicialDeTareas;
use App\Modules\Pedidos\Contracts\TareaInicial;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\AdjuntosService;
use App\Shared\Adjuntos\Application\RegistrarLinkAction;
use App\Shared\CargaInicial\CargaRechazada;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;

/**
 * `CargaInicialDeTareas` con las piezas del alta (W-08, `CrearPedidoAction`): las mismas reglas de fechas, obra y
 * a quién se le da (RN-PED-03, RN-PED-23), los links como adjuntos `pedido` y la actividad `creado` (con la marca de
 * carga inicial, RN-IMP-39). A diferencia del alta: el solicitante es el de la minuta o ninguno, la fecha de pedido
 * puede faltar, el estado es el de la minuta y **no** se dispara `PedidoAsignado` (RN-IMP-37).
 */
final readonly class CargaInicialDeTareasConCasosDeUso implements CargaInicialDeTareas
{
    public const EN_CURSO_SIN_PROPIETARIO = 'Una tarea En curso tiene que tener propietario: elegí quién la tiene.';

    public const BLOQUEADA_SIN_MOTIVO = 'Está Bloqueada sin motivo: escribí el motivo en Notas.';

    public function __construct(
        private Connection $db,
        private ObrasConsulta $obras,
        private Asignables $asignables,
        private CalculoDePrioridad $prioridad,
        private RegistrarLinkAction $links,
        private AdjuntosService $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    public function crear(int $actorId, TareaInicial $tarea): int
    {
        return CargaRechazada::traducir(fn (): int => $this->db->transaction(function () use ($actorId, $tarea): int {
            $estado = PedidoEstado::from($tarea->estado);
            ReglasDeFechas::validar($tarea->fechaPedido, $tarea->periodoInicio, $tarea->periodoFin);
            $sectorId = $this->sectorYPropietario($tarea, $estado);

            if ($estado === PedidoEstado::Bloqueada && ($tarea->notas === null || trim($tarea->notas) === '')) {
                throw CargaRechazada::por(self::BLOQUEADA_SIN_MOTIVO);
            }

            [$prioridad, $manual] = $this->prioridadDe($tarea);
            $pedido = new Pedido([
                'obra_id' => $tarea->obraId,
                'tarea' => $tarea->tarea,
                'sector_id' => $sectorId,
                'propietario_id' => $tarea->propietarioId,
                'solicitante_id' => $tarea->solicitanteId,
                'con_quien' => $tarea->conQuien,
                'fecha_pedido' => $tarea->fechaPedido,
                'fecha_entrega' => $tarea->fechaEntrega,
                'periodo_inicio' => $tarea->periodoInicio,
                'periodo_fin' => $tarea->periodoFin,
                'aclaraciones' => $estado === PedidoEstado::Bloqueada ? null : $tarea->notas,
                'prioridad' => $prioridad,
                'prioridad_manual' => $manual,
                'estado' => $estado,
                'carga_inicial' => true,
            ]);
            $pedido->motivo_bloqueo = $estado === PedidoEstado::Bloqueada ? $tarea->notas : null;
            $pedido->save();

            $this->actividad->registrar($pedido, CrearPedidoAction::ACCION);
            $actor = User::query()->findOrFail($actorId);
            $adjuntos = array_map(fn (string $url): int => $this->links->handle($actor, Pedido::CONTEXTO_ADJUNTOS, $url, null)->id, $tarea->links);
            $this->adjuntos->asociar($actorId, $adjuntos, $pedido, Pedido::CONTEXTO_ADJUNTOS);

            return $pedido->id;
        }));
    }

    public function prioridadCalculada(?string $fechaPedido, string $fechaEntrega): ?string
    {
        return $this->prioridad->calculada(
            $fechaPedido === null ? null : CarbonImmutable::parse($fechaPedido),
            CarbonImmutable::parse($fechaEntrega),
        )?->value;
    }

    /**
     * La persona activa de un sector con Tareas ≥ Ver, o "(en el sector)" con un sector activo que la pueda ver
     * (RN-IMP-33); una En curso necesita propietario (RN-IMP-34).
     */
    private function sectorYPropietario(TareaInicial $tarea, PedidoEstado $estado): int
    {
        if ($tarea->obraId !== null && ! $this->obras->existe($tarea->obraId)) {
            throw CargaRechazada::por('Elegí la obra o \'Sin obra\'.');
        }

        if ($tarea->propietarioId !== null) {
            return $this->asignables->persona($tarea->propietarioId)->sector_id;
        }

        if ($estado === PedidoEstado::EnCurso) {
            throw CargaRechazada::por(self::EN_CURSO_SIN_PROPIETARIO);
        }

        return $this->asignables->sectorConGente($tarea->sectorId ?? 0)->id;
    }

    /**
     * RN-IMP-35: la de la minuta; vacía (o P3) = la calculada si están las dos fechas, si no Normal. Queda corregida
     * a mano solo si se puede calcular y la de la minuta difiere.
     *
     * @return array{Prioridad, bool}
     */
    private function prioridadDe(TareaInicial $tarea): array
    {
        $minuta = $tarea->prioridad === null ? null : Prioridad::from($tarea->prioridad);
        $calculada = $this->prioridadCalculada($tarea->fechaPedido, $tarea->fechaEntrega);

        if ($calculada === null) {
            return [$minuta ?? Prioridad::Normal, false];
        }

        $calculada = Prioridad::from($calculada);

        return $minuta === null || $minuta === $calculada ? [$calculada, false] : [$minuta, true];
    }
}
