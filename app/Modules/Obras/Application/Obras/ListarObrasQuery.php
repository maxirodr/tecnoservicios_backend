<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Ports\AvanceGastoDeObra;
use App\Modules\Obras\Application\Ports\PedidosDeObra;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Infrastructure\Consultas\FiltroObras;
use App\Modules\Obras\Infrastructure\Consultas\ObrasQuery;
use App\Modules\Obras\Infrastructure\Consultas\PorBusqueda;
use App\Modules\Obras\Infrastructure\Consultas\PorResponsable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * F-15 / HU-013 (W-05): obras de una pestaña con contadores de las tres (RN-OBR-01, 06), búsqueda
 * (RN-OBR-05), filtro por responsable, orden de RN-OBR-07 y alcance "solo sus obras". Pedidos
 * abiertos y % gastado salen de los puertos en una consulta por página (RN-OBR-03, 04).
 */
final readonly class ListarObrasQuery
{
    public const POR_PAGINA = 25;

    public function __construct(
        private ObrasQuery $obras,
        private AccesoObra $acceso,
        private PedidosDeObra $pedidos,
        private AvanceGastoDeObra $avance,
    ) {}

    public function handle(User $user, ObraEstado $estado, ?string $buscar, ?int $responsableId, int $pagina): ListadoObras
    {
        $filtros = $this->filtros($user, $buscar, $responsableId);
        $numeros = $this->obras->idsOrdenados($filtros, $estado);
        $ids = array_keys($numeros);
        // "En ejecución" ordena por % gastado: se pide una vez para todas y se reusa en la página (TL-04).
        $porcentajes = null;

        if ($estado === ObraEstado::EnEjecucion) {
            $porcentajes = $this->avance->porcentajesGastados($ids);
            $ids = self::porPorcentaje($ids, $porcentajes);
        }

        $idsPagina = array_slice($ids, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA);
        $filas = $this->filas($user, $idsPagina, $porcentajes);

        return new ListadoObras(
            new LengthAwarePaginator($filas, count($ids), self::POR_PAGINA, $pagina, ['path' => LengthAwarePaginator::resolveCurrentPath()]),
            $this->obras->contadores($filtros),
            $this->acceso->verPedidos($user) && ($buscar === null || trim($buscar) === '') ? $this->pedidos->contarAbiertosSinObra() : null,
        );
    }

    /**
     * @return list<FiltroObras>
     */
    private function filtros(User $user, ?string $buscar, ?int $responsableId): array
    {
        $filtros = [];

        if ($this->acceso->soloPropias($user, ClaveFuncion::Obras)) {
            $filtros[] = new PorResponsable($user->id);
        }

        if ($responsableId !== null) {
            $filtros[] = new PorResponsable($responsableId);
        }

        if ($buscar !== null && trim($buscar) !== '') {
            $filtros[] = new PorBusqueda($buscar);
        }

        return $filtros;
    }

    /**
     * @param  list<int>  $ids
     * @param  array<int, numeric-string|null>|null  $porcentajes  ya pedidos para estas obras (o más), o null
     * @return list<FilaObra>
     */
    private function filas(User $user, array $ids, ?array $porcentajes): array
    {
        $pedidos = $this->pedidos->contarAbiertosPorObra($ids);
        $porcentajes ??= $this->avance->porcentajesGastados($ids);
        $pasadas = $this->avance->pasadas($ids);
        $rendicionTodas = $this->acceso->puedeNivel($user, ClaveFuncion::Rendicion, Nivel::Ver)
            && ! $this->acceso->soloPropias($user, ClaveFuncion::Rendicion);
        $rendicionPropias = $this->acceso->puedeNivel($user, ClaveFuncion::Rendicion, Nivel::Ver);

        return array_values($this->obras->filas($ids)->map(function (Obra $obra) use ($user, $pedidos, $porcentajes, $pasadas, $rendicionTodas, $rendicionPropias): FilaObra {
            // RN-OBR-04: el % solo a quien ve la rendición de esa obra.
            $veRendicion = $rendicionTodas || ($rendicionPropias && $obra->esResponsable($user));
            $porcentaje = $veRendicion ? ($porcentajes[$obra->id] ?? null) : null;

            return new FilaObra(
                $obra,
                $pedidos[$obra->id] ?? null,
                $porcentaje,
                $porcentaje === null ? null : ($pasadas[$obra->id] ?? false),
            );
        })->all());
    }

    /**
     * RN-OBR-07: En ejecución por % gastado de mayor a menor; sin dato, al final por N.º (ya vienen así).
     *
     * @param  list<int>  $ids
     * @param  array<int, numeric-string|null>  $porcentajes
     * @return list<int>
     */
    private static function porPorcentaje(array $ids, array $porcentajes): array
    {
        $posicion = array_flip($ids);
        usort($ids, static function (int $a, int $b) use ($porcentajes, $posicion): int {
            $pa = $porcentajes[$a] ?? null;
            $pb = $porcentajes[$b] ?? null;

            if ($pa === null || $pb === null || bccomp($pa, $pb, 2) === 0) {
                return [$pa === null, $posicion[$a]] <=> [$pb === null, $posicion[$b]];
            }

            return bccomp($pb, $pa, 2);
        });

        return $ids;
    }
}
