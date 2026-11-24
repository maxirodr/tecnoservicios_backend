<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Obras\Contracts\PresupuestoDeObra;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;

/**
 * La cuenta de la rendición (rendicion.md §4.3, N1…N12; RN-REN-09…22): lo presupuestado (Obras) contra lo
 * gastado por rubro (Gastos), con EPP sumando en INSUMOS. Sumas exactas al centavo; solo los % se redondean.
 */
final readonly class CalculoDeRendicion
{
    public function __construct(private CatalogoDeRubros $catalogo) {}

    /**
     * @param  array<string, numeric-string>  $gastadoPorRubro  rubroClave => total, sin aplicar EPP → INSUMOS
     */
    public function calcular(PresupuestadoDeObra $presupuestado, array $gastadoPorRubro): ResumenDeRendicion
    {
        $situacion = Situacion::de($presupuestado->presupuestos);
        $incluye = $this->incluidos();
        $filas = [];

        foreach ($this->catalogo->presupuestables() as $rubro) {
            $claves = [$rubro->clave, ...($incluye[$rubro->clave] ?? [])];
            $gastado = Decimal::suma(array_map(static fn (string $c): string => $gastadoPorRubro[$c] ?? '0', $claves));
            $presupuestadoDelRubro = $presupuestado->porRubro[$rubro->clave] ?? '0';
            $filas[] = $situacion->comparaRubros()
                ? self::filaComparada($rubro->clave, $rubro->nombre, $incluye[$rubro->clave] ?? [], $rubro->cargaPorPeriodo, Decimal::suma([$presupuestadoDelRubro]), $gastado)
                : new FilaDeRubro($rubro->clave, $rubro->nombre, $incluye[$rubro->clave] ?? [], $rubro->cargaPorPeriodo, null, $gastado, null, null, false, false);
        }

        return new ResumenDeRendicion(
            $situacion,
            $presupuestado->presupuestos,
            self::numeros($presupuestado->presupuestos, Situacion::MODO_SIN_CARGAR),
            $filas,
            self::sinDesglose($presupuestado),
            self::total($situacion, $presupuestado, Decimal::suma($gastadoPorRubro), $filas),
        );
    }

    /**
     * N1, N6, N8 y N10: pasado es estricto al centavo; con presupuesto 0 y gasto, pasado y "sin presupuesto".
     *
     * @param  list<string>  $incluye
     * @param  numeric-string  $presupuestado
     * @param  numeric-string  $gastado
     */
    private static function filaComparada(string $clave, string $nombre, array $incluye, bool $porPeriodo, string $presupuestado, string $gastado): FilaDeRubro
    {
        $pasado = Decimal::mayor($gastado, $presupuestado);

        return new FilaDeRubro(
            $clave,
            $nombre,
            $incluye,
            $porPeriodo,
            $presupuestado,
            $gastado,
            Decimal::resta($presupuestado, $gastado),
            Porcentaje::de($gastado, $presupuestado),
            $pasado,
            $pasado && Decimal::esCero($presupuestado),
        );
    }

    /**
     * @param  numeric-string  $gastado
     * @param  list<FilaDeRubro>  $filas
     */
    private static function total(Situacion $situacion, PresupuestadoDeObra $presupuestado, string $gastado, array $filas): TotalDeObra
    {
        if (! $situacion->tienePresupuesto()) {
            return new TotalDeObra(null, $gastado, null, null, false, null);
        }

        $total = Decimal::suma([$presupuestado->total]);
        $pasados = count(array_filter($filas, static fn (FilaDeRubro $f): bool => $f->pasado));

        return new TotalDeObra(
            $total,
            $gastado,
            Decimal::resta($total, $gastado),
            Porcentaje::de($gastado, $total),
            Decimal::mayor($gastado, $total),
            $situacion->comparaRubros() ? $pasados : null,
        );
    }

    private static function sinDesglose(PresupuestadoDeObra $presupuestado): ?SinDesglose
    {
        $numeros = self::numeros($presupuestado->presupuestos, Situacion::MODO_SOLO_TOTAL);

        return $numeros === [] ? null : new SinDesglose(Decimal::suma([$presupuestado->sinDesglose]), $numeros);
    }

    /**
     * @param  list<PresupuestoDeObra>  $presupuestos
     * @return list<string>
     */
    private static function numeros(array $presupuestos, string $modo): array
    {
        return array_values(array_map(
            static fn (PresupuestoDeObra $p): string => $p->numero,
            array_filter($presupuestos, static fn (PresupuestoDeObra $p): bool => $p->modoCarga === $modo),
        ));
    }

    /**
     * Qué rubros imputables suman en cada rubro del resumen ("insumos" => ["epp"]).
     *
     * @return array<string, list<string>>
     */
    private function incluidos(): array
    {
        $incluye = [];

        foreach ($this->catalogo->imputables() as $rubro) {
            $destino = $this->catalogo->resumeEn($rubro->clave);
            if ($destino !== null) {
                $incluye[$destino][] = $rubro->clave;
            }
        }

        return $incluye;
    }
}
