<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Pedidos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Prioridad\Anticipacion;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\AdjuntosService;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * F-33 / HU-031: editar el pedido en cualquier estado (RN-PED-28). Cada dato que cambia queda en
 * `actividades` con antes → después en la misma transacción; guardar sin cambios no registra (RN-PED-29).
 * La obra se corrige acá (DU-P05); propietario, sector y estado no. Editar no avisa (DU-P24).
 */
final readonly class ActualizarPedidoAction
{
    public const ACCION = 'editado';

    /** Para el texto "(la prioridad sigue Normal: 9 → 11 días)": no es un campo del pedido. */
    public const DIAS = 'dias_anticipacion';

    /** La prioridad que siguió igual al cambiar una fecha ("la prioridad sigue Normal"). */
    public const PRIORIDAD_SIGUE = 'prioridad_sigue';

    private const FECHAS = ['fecha_pedido', 'fecha_entrega'];

    public function __construct(
        private Connection $db,
        private BuscarPedido $buscar,
        private AccesoPedido $acceso,
        private ObrasConsulta $obras,
        private PrioridadAlEditar $prioridad,
        private AdjuntosService $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException|HttpResponseException 403 · 404 · 409 (versión)
     */
    public function handle(User $actor, int $id, CambiosPedido $cambios, ?CarbonInterface $version = null): Pedido
    {
        return $this->db->transaction(function () use ($actor, $id, $cambios, $version): Pedido {
            $pedido = $this->buscar->bloqueado($actor, $id);
            BuscarPedido::exigir($this->acceso->editar($actor, $pedido));
            BuscarPedido::exigir(! $cambios->tocaPrioridad() || $this->acceso->corregirPrioridad($actor, $pedido), 'Solo quien la pidió puede corregir la prioridad.');
            VersionDeFila::bloquearYVerificar($pedido, $version);

            if ($cambios->cambiaObra && $cambios->obraId !== null && ! $this->obras->existe($cambios->obraId)) {
                throw ValidationException::withMessages(['obra_id' => ['Elegí la obra o \'Sin obra\'.']]);
            }

            $antes = $this->valores($pedido);
            $pedido->fill($cambios->textos);

            if ($cambios->cambiaObra) {
                $pedido->obra_id = $cambios->obraId;
            }

            ReglasDeFechas::validar(
                $pedido->fecha_pedido?->toDateString(),
                $pedido->periodo_inicio?->toDateString(),
                $pedido->periodo_fin?->toDateString(),
            );
            $this->prioridad->aplicar($pedido, $cambios, $pedido->isDirty(self::FECHAS));
            $pedido->save();

            $diferencias = $this->diferencias($antes, $this->valores($pedido));

            if ($diferencias !== []) {
                $this->actividad->registrar($pedido, self::ACCION, $diferencias);
            }

            if ($cambios->adjuntos !== null && $this->cambiarAdjuntos($actor, $pedido, $cambios->adjuntos) && ! $pedido->wasChanged()) {
                $pedido->touch();
            }

            return $pedido;
        });
    }

    /**
     * @param  list<int>  $nuevos
     */
    private function cambiarAdjuntos(User $actor, Pedido $pedido, array $nuevos): bool
    {
        $actuales = $pedido->adjuntos()->pluck('id')->map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)->all();
        $agregar = array_values(array_diff($nuevos, $actuales));
        $quitar = array_values(array_diff($actuales, $nuevos));

        $this->adjuntos->asociar($actor->id, $agregar, $pedido, Pedido::CONTEXTO_ADJUNTOS);
        $this->adjuntos->quitar($quitar, $pedido);

        return $agregar !== [] || $quitar !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function valores(Pedido $pedido): array
    {
        $obra = $pedido->obra_id === null ? null : $this->obras->resumen($pedido->obra_id);

        return [
            'tarea' => $pedido->tarea,
            'obra' => $obra?->nombreTrabajo,
            'fecha_pedido' => $pedido->fecha_pedido?->toDateString(),
            'fecha_entrega' => $pedido->fecha_entrega?->toDateString(),
            'periodo_inicio' => $pedido->periodo_inicio?->toDateString(),
            'periodo_fin' => $pedido->periodo_fin?->toDateString(),
            'con_quien' => $pedido->con_quien,
            'aclaraciones' => $pedido->aclaraciones,
            'prioridad' => $pedido->prioridad->value.($pedido->prioridad_manual ? ' (corregida a mano)' : ''),
            self::DIAS => Anticipacion::dias($pedido->fecha_pedido, $pedido->fecha_entrega),
        ];
    }

    /**
     * Los días solo acompañan a un cambio de fecha (para el texto del historial).
     *
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     * @return array<string, array{antes: mixed, despues: mixed}>
     */
    private function diferencias(array $antes, array $despues): array
    {
        $cambios = [];

        foreach ($despues as $campo => $valor) {
            if ($campo !== self::DIAS && $antes[$campo] !== $valor) {
                $cambios[$campo] = ['antes' => $antes[$campo], 'despues' => $valor];
            }
        }

        if (array_intersect_key($cambios, array_flip(self::FECHAS)) !== []) {
            $cambios[self::DIAS] = ['antes' => $antes[self::DIAS], 'despues' => $despues[self::DIAS]];

            if (! isset($cambios['prioridad'])) {
                $cambios[self::PRIORIDAD_SIGUE] = ['antes' => $antes['prioridad'], 'despues' => $despues['prioridad']];
            }
        }

        return $cambios;
    }
}
