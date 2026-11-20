<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\Acceso\AccesoPedido;
use App\Modules\Pedidos\Application\FechaEmpresa;
use App\Modules\Pedidos\Domain\Models\Pedido;
use App\Modules\Pedidos\Infrastructure\Consultas\EnElSector;
use App\Modules\Pedidos\Infrastructure\Consultas\EntregaEntre;
use App\Modules\Pedidos\Infrastructure\Consultas\FiltroPedidos;
use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use App\Modules\Pedidos\Infrastructure\Consultas\PorEstado;
use App\Modules\Pedidos\Infrastructure\Consultas\PorObra;
use App\Modules\Pedidos\Infrastructure\Consultas\PorPrioridad;
use App\Modules\Pedidos\Infrastructure\Consultas\PorPropietario;
use App\Modules\Pedidos\Infrastructure\Consultas\PorSector;
use App\Modules\Pedidos\Infrastructure\Consultas\PorSolicitante;
use App\Modules\Pedidos\Infrastructure\Consultas\SinObra;
use App\Modules\Pedidos\Infrastructure\Consultas\Vencidos;
use App\Modules\Pedidos\Infrastructure\Consultas\VisiblesPara;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

/**
 * Todos los pedidos (W-24, RN-PED-42…45), Servicios generales (W-25, `obra=sin_obra`) y la pestaña
 * Pedidos de la obra (W-07, `obra=<id>`): siempre se aplica antes el alcance de quien mira (RN-PED-33).
 */
final readonly class TodosLosPedidos
{
    public const POR_PAGINA = 50;

    /** Tamaño de cada tanda al exportar: baja todo lo filtrado sin cargarlo entero en memoria. */
    private const TANDA_EXPORTAR = 500;

    public function __construct(private AccesoPedido $acceso) {}

    /**
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function pagina(User $user, FiltrosDePedidos $filtros, int $pagina): LengthAwarePaginator
    {
        return $this->consulta($user, $filtros, ListasDeInicio::CON)
            ->paginate(self::POR_PAGINA, ['pedidos.*'], 'page', $pagina);
    }

    /** El "9" de "6 de 9 pedidos con estos filtros": todos los que la persona puede ver. */
    public function totalVisibles(User $user): int
    {
        return VisiblesPara::aplicar(Pedido::query(), $this->acceso->visibilidad($user))->count();
    }

    /**
     * Todo lo filtrado, sin paginar y en el orden de la tabla (RN-PED-45).
     *
     * @return LazyCollection<int, Pedido>
     */
    public function todos(User $user, FiltrosDePedidos $filtros): LazyCollection
    {
        return $this->consulta($user, $filtros, [...ListasDeInicio::CON, 'completadoPor'])->lazy(self::TANDA_EXPORTAR);
    }

    /**
     * @param  list<string>  $con
     * @return Builder<Pedido>
     */
    private function consulta(User $user, FiltrosDePedidos $filtros, array $con): Builder
    {
        $query = VisiblesPara::aplicar(PedidosQuery::nueva($con), $this->acceso->visibilidad($user));

        foreach (self::filtros($filtros) as $filtro) {
            $filtro->aplicar($query);
        }

        return PedidosQuery::ordenDeTabla($query, $filtros->orden, $filtros->direccion);
    }

    /**
     * @return list<FiltroPedidos>
     */
    private static function filtros(FiltrosDePedidos $f): array
    {
        $filtros = [new PorEstado($f->estado), new EntregaEntre($f->entregaDesde, $f->entregaHasta)];

        if ($f->obra !== null) {
            $filtros[] = $f->obra === PedidosQuery::SIN_OBRA ? new SinObra : new PorObra((int) $f->obra);
        }

        if ($f->propietario !== null) {
            $filtros[] = $f->propietario === PedidosQuery::EN_SECTOR ? new EnElSector : new PorPropietario((int) $f->propietario);
        }

        if ($f->sectorId !== null) {
            $filtros[] = new PorSector($f->sectorId);
        }

        if ($f->solicitanteId !== null) {
            $filtros[] = new PorSolicitante($f->solicitanteId);
        }

        if ($f->prioridad !== null) {
            $filtros[] = new PorPrioridad($f->prioridad);
        }

        if ($f->soloVencidos) {
            $filtros[] = new Vencidos(FechaEmpresa::hoyTexto());
        }

        return $filtros;
    }
}
