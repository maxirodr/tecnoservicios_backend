<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Estado;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Ports\PedidoAbiertoResumen;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Estados\ObraStateMachine;
use App\Modules\Obras\Domain\Estados\TransicionNoPermitida;
use App\Modules\Obras\Domain\Events\ObraEstadoCambiado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * F-21 / HU-017 (W-22): cambia el estado con `ObraStateMachine` (RN-OBR-29). Lo hacen el responsable
 * de la obra o `estado_obra = editar` (RN-OBR-30). Al pasar a Terminada, si apareció un pedido
 * abierto que no estaba en la lista confirmada, 409 con la lista actualizada (obras.md §6, paso 4).
 */
final readonly class CambiarEstadoObraAction
{
    public const ACCION = 'estado_cambiado';

    public function __construct(
        private Connection $db,
        private AccesoObra $acceso,
        private ObraStateMachine $estados,
        private PedidosDeObra $pedidos,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @param  list<int>  $pedidosConfirmados  ids de los pedidos abiertos que vio quien confirma
     *
     * @throws AuthorizationException|ValidationException|HttpResponseException
     */
    public function handle(User $actor, Obra $obra, ObraEstado $destino, array $pedidosConfirmados): Obra
    {
        if (! $this->acceso->cambiarEstado($actor, $obra)) {
            throw new AuthorizationException;
        }

        return $this->db->transaction(function () use ($actor, $obra, $destino, $pedidosConfirmados): Obra {
            $obra = Obra::query()->lockForUpdate()->findOrFail($obra->id);
            $antes = $obra->estado;

            try {
                $this->estados->transicionar($obra, $destino, $actor->id, Carbon::now());
            } catch (TransicionNoPermitida $e) {
                throw ValidationException::withMessages(['estado' => [$e->getMessage()]]);
            }

            if ($destino === ObraEstado::Terminada) {
                $this->exigirPedidosConfirmados($obra, $pedidosConfirmados);
            }

            $obra->save();
            $this->actividad->registrar($obra, self::ACCION, ['estado' => ['antes' => $antes->value, 'despues' => $destino->value]]);
            $this->eventos->dispatch(new ObraEstadoCambiado($obra->id, $antes, $destino));

            return $obra;
        });
    }

    /**
     * No bloquea por tener pedidos abiertos (DU-12), solo por pedidos que quien confirma no vio.
     *
     * @param  list<int>  $confirmados
     *
     * @throws HttpResponseException
     */
    private function exigirPedidosConfirmados(Obra $obra, array $confirmados): void
    {
        $abiertos = $this->pedidos->abiertosDe($obra->id);
        $nuevos = array_filter($abiertos, static fn (PedidoAbiertoResumen $p): bool => ! in_array($p->id, $confirmados, true));

        if ($nuevos === []) {
            return;
        }

        throw new HttpResponseException(ApiResponse::error(
            'Se cargaron pedidos nuevos en la obra mientras confirmabas. Revisalos antes de pasarla a Terminada.',
            409,
            ['pedidos_abiertos' => array_map(PedidoAbiertoArray::de(...), $abiertos)],
        ));
    }
}
