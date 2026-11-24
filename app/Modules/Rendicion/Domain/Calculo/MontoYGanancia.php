<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Calculo;

use App\Modules\Obras\Contracts\MontoDeObra;
use App\Modules\Obras\Contracts\MontoDePresupuesto;

/**
 * Bloque "Monto de la obra y ganancia" (N13…N15, RN-REN-23…25, resuelta DU-R01): ganancia prevista = monto −
 * costo presupuestado; ganancia al día = monto − gastado; los dos % sobre el monto (su "Ganancia ÷ Cobrado").
 * Sin presupuesto cargado, la ganancia prevista y su % son null.
 */
final readonly class MontoYGanancia
{
    /**
     * @param  numeric-string  $montoObra
     * @param  list<MontoDePresupuesto>  $porPresupuesto
     * @param  list<string>  $sinMonto
     * @param  numeric-string|null  $gananciaPrevista
     * @param  numeric-string|null  $porcentajePrevista
     * @param  numeric-string  $gananciaAlDia
     * @param  numeric-string|null  $porcentajeAlDia
     */
    public function __construct(
        public string $montoObra,
        public array $porPresupuesto,
        public array $sinMonto,
        public ?string $gananciaPrevista,
        public ?string $porcentajePrevista,
        public string $gananciaAlDia,
        public ?string $porcentajeAlDia,
    ) {}

    public static function de(MontoDeObra $monto, TotalDeObra $total): self
    {
        $montoObra = Decimal::suma([$monto->total]);
        $prevista = $total->presupuestado === null || Decimal::esCero($total->presupuestado)
            ? null
            : Decimal::resta($montoObra, $total->presupuestado);
        $alDia = Decimal::resta($montoObra, $total->gastado);

        return new self(
            $montoObra,
            $monto->porPresupuesto,
            array_values(array_map(
                static fn (MontoDePresupuesto $p): string => $p->numero,
                array_filter($monto->porPresupuesto, static fn (MontoDePresupuesto $p): bool => $p->monto === null),
            )),
            $prevista,
            $prevista === null ? null : Porcentaje::de($prevista, $montoObra),
            $alDia,
            Porcentaje::de($alDia, $montoObra),
        );
    }
}
