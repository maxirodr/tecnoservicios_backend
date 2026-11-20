<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\Acceso\BuscarPedido;
use App\Modules\Pedidos\Application\Acceso\MiGente;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use App\Modules\Pedidos\Infrastructure\Consultas\VisiblesPara;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Calendario (W-03, RN-PED-47/48): los pedidos (abiertos y completados, que el front tacha) con la entrega
 * en el rango o con un período que lo cruza. "Mis tareas" (soy propietario o está en mi sector sin
 * propietario) o "Mi gente" (el propietario es de mi gente, sin incluirme; resuelta DU-P14), más el filtro
 * por obra. Siempre con el alcance de quien mira (RN-PED-33).
 */
final readonly class CalendarioDePedidos
{
    public const MIAS = 'mias';

    public const MI_GENTE = 'mi_gente';

    public const CON = ['obra', 'sector', 'propietario'];

    public function __construct(
        private AccesoPedido $acceso,
        private MiGente $gente,
    ) {}

    /**
     * @return Collection<int, Pedido>
     *
     * @throws HttpResponseException 403 "Mi gente" sin gente a cargo (RN-PED-35)
     */
    public function handle(User $user, string $desde, string $hasta, string $de, int|string|null $obra): Collection
    {
        $query = VisiblesPara::aplicar(PedidosQuery::nueva(self::CON), $this->acceso->visibilidad($user));

        if ($de === self::MI_GENTE) {
            $this->deMiGente($query, $user);
        } else {
            PedidosQuery::misTareas($query, $user);
        }

        return PedidosQuery::porObra($query, $obra)
            ->where(fn (Builder $q) => $q->whereBetween('pedidos.fecha_entrega', [$desde, $hasta])
                ->orWhere(fn (Builder $p) => $p->where('pedidos.periodo_inicio', '<=', $hasta)->where('pedidos.periodo_fin', '>=', $desde)))
            ->orderBy('pedidos.fecha_entrega')
            ->orderBy('pedidos.id')
            ->get();
    }

    /**
     * @param  Builder<Pedido>  $query
     */
    private function deMiGente(Builder $query, User $user): void
    {
        BuscarPedido::exigir($this->gente->tieneGenteActiva($user), GenteConCarga::SIN_GENTE);
        $ids = $this->gente->ids($user);

        $query->whereNotNull('pedidos.propietario_id')->where('pedidos.propietario_id', '!=', $user->id);

        if ($ids !== null) {
            $query->whereIn('pedidos.propietario_id', $ids);
        }
    }
}
