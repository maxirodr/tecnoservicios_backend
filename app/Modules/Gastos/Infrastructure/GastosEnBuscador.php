<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Infrastructure;

use App\Modules\Gastos\Application\Acceso\AccesoGasto;
use App\Modules\Gastos\Application\Lecturas\LecturaDeGasto;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Gastos\Domain\NumeroComprobante;
use App\Modules\Gastos\Infrastructure\Consultas\CriterioDeRenglones;
use App\Modules\Gastos\Infrastructure\Consultas\GastosQuery;
use App\Modules\Gastos\Infrastructure\Consultas\VisiblesPara;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Busqueda\FuenteBusqueda;
use App\Shared\Busqueda\ResultadoBusqueda;
use Illuminate\Database\Eloquent\Builder;

/**
 * Grupo "Gastos" del buscador general (F-03, HU-085, RN-GAS-44, resuelta DU-G07): por número de factura o
 * de remito, con 3 o más dígitos, ignorando ceros a la izquierda, guiones y espacios. Anulados también,
 * marcados. La más nueva primero. Con el alcance de quien busca (el importe, el de sus renglones); sin
 * `gastos` ≥ ver no viaja. No busca en el concepto ni por la obra.
 */
final readonly class GastosEnBuscador implements FuenteBusqueda
{
    public const TIPO = 'gastos';

    private const MINIMO_DIGITOS = 3;

    /** Cada tramo sin ceros a la izquierda y sin separadores, igual que `NumeroComprobante::paraBuscar`. */
    private const NUMERO_SQL = 'REGEXP_REPLACE(REGEXP_REPLACE(UPPER(gastos.numero), \'(^|[^0-9])0+(?=[0-9])\', \'$1\'), \'[^0-9A-Z]\', \'\')';

    public function __construct(
        private AccesoGasto $acceso,
        private LecturaDeGasto $lectura,
    ) {}

    public function puedeBuscar(User $user): bool
    {
        return $this->acceso->puede($user, ClaveFuncion::Gastos, Nivel::Ver);
    }

    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda
    {
        if (NumeroComprobante::digitos($texto) < self::MINIMO_DIGITOS) {
            return new ResultadoBusqueda(0, []);
        }

        $buscado = NumeroComprobante::paraBuscar($texto);
        $cantidad = $this->encontrados($user, $buscado)->count();
        $primeros = GastosQuery::ordenados($this->encontrados($user, $buscado))->with(LecturaDeGasto::FILA)->limit($limite)->get();

        return new ResultadoBusqueda($cantidad, array_values($primeros->map(function (Gasto $gasto) use ($user): array {
            $leido = $this->lectura->de($user, $gasto);

            return [
                'id' => $gasto->id,
                'documento' => $leido->documento,
                'proveedor' => ['id' => $gasto->proveedor->id, 'nombre' => $gasto->proveedor->nombre, 'es_generico' => $gasto->proveedor->es_generico],
                'fecha' => $gasto->fecha->toDateString(),
                'total' => $leido->total,
                'anulado' => $gasto->anulado(),
            ];
        })->all()));
    }

    /**
     * @return Builder<Gasto>
     */
    private function encontrados(User $user, string $buscado): Builder
    {
        $visibles = $this->acceso->obrasVisibles($user);
        $criterio = new CriterioDeRenglones($visibles === null ? [] : [new VisiblesPara($visibles)]);

        return GastosQuery::filtrados([], $criterio)
            ->whereNotNull('gastos.numero')
            ->whereRaw(self::NUMERO_SQL.' LIKE ?', ['%'.addcslashes($buscado, '\\%_').'%']);
    }
}
