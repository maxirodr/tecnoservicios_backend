<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Infrastructure;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Ports\PedidoAbiertoResumen;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Domain\Enums\PedidoEstado;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Domain\Visibilidad;
use App\Modules\Pedidos\Infrastructure\Consultas\VisiblesPara;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pedidos implementa el puerto de Obras (RN-PED-52, HU-059 c4). Los conteos salen en **una** consulta por
 * página y cuentan solo lo que ve la persona autenticada (resuelta DU-P17, sin cambiar la firma del
 * puerto; con alcance "todas" no cambia nada). `abiertosDe` (W-22) lista todos los abiertos de la obra.
 */
final readonly class PedidosDeObraEloquent implements PedidosDeObra
{
    public function __construct(
        private AuthFactory $auth,
        private AccesoPedido $acceso,
    ) {}

    /** Con los accesos "completar · reasignar" de quien mira (resuelta DU-P12). */
    public function abiertosDe(int $obraId): array
    {
        $user = $this->usuario();

        return array_values(Pedido::query()
            ->with(['propietario', 'sector'])
            ->where('obra_id', $obraId)
            ->whereIn('estado', PedidoEstado::abiertos())
            ->orderBy('fecha_entrega')
            ->orderBy('id')
            ->get()
            ->map(function (Pedido $p) use ($user): PedidoAbiertoResumen {
                $puede = $user === null ? ['completar' => false, 'reasignar' => false] : $this->acceso->alTerminarLaObra($user, $p);

                return new PedidoAbiertoResumen(
                    $p->id,
                    $p->tarea,
                    $p->propietario_id,
                    $p->propietario?->nombre,
                    $p->sector_id,
                    $p->sector->nombre,
                    $p->estado->value,
                    $puede['completar'],
                    $puede['reasignar'],
                );
            })
            ->all());
    }

    public function contarAbiertosPorObra(array $obraIds): array
    {
        if ($obraIds === []) {
            return [];
        }

        $conteos = $this->abiertos()
            ->whereIn('obra_id', $obraIds)
            ->groupBy('obra_id')
            ->selectRaw('obra_id, COUNT(*) as cantidad')
            ->toBase()
            ->pluck('cantidad', 'obra_id');

        $resultado = [];

        foreach ($obraIds as $id) {
            $cantidad = $conteos->get($id);
            $resultado[$id] = is_numeric($cantidad) ? (int) $cantidad : 0;
        }

        return $resultado;
    }

    public function contarAbiertosSinObra(): int
    {
        return $this->abiertos()->whereNull('obra_id')->count();
    }

    /**
     * @return Builder<Pedido>
     */
    private function abiertos(): Builder
    {
        $user = $this->usuario();
        $visibilidad = $user === null ? Visibilidad::todos() : $this->acceso->visibilidad($user);

        return VisiblesPara::aplicar(Pedido::query()->whereIn('pedidos.estado', PedidoEstado::abiertos()), $visibilidad);
    }

    private function usuario(): ?User
    {
        $user = $this->auth->guard()->user();

        return $user instanceof User ? $user : null;
    }
}
