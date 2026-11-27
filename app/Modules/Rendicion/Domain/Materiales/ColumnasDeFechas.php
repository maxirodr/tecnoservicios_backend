<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Materiales;

/**
 * Columnas de fecha de W-48 (RN-REN-50, resuelta DU-R06): las 6 fechas más recientes con movimientos, de la
 * más vieja a la más nueva; las anteriores se juntan en "Hasta {fecha}" (la más nueva de las agrupadas).
 * Con carga inicial (`importacion.md` RN-IMP-30), "Hasta {corte} · Carga inicial" es una primera columna fija que
 * no cuenta entre las 6; si además se agrupan otras fechas, van en esa misma columna con la más nueva
 * ("Hasta {fecha} · incluye carga inicial").
 */
final readonly class ColumnasDeFechas
{
    public const CANTIDAD = 6;

    /**
     * @param  list<string>  $fechas
     */
    public function __construct(
        public array $fechas,
        public ?string $hasta,
        public bool $cargaInicial = false,
        public bool $agrupaOtras = false,
    ) {}

    /**
     * @param  list<string>  $fechasConMovimientos  sin las de la carga inicial
     * @param  string|null  $corte  la fecha de corte de la carga inicial de retirado, si la obra tiene
     */
    public static function de(array $fechasConMovimientos, ?string $corte = null): self
    {
        $fechas = array_values(array_unique($fechasConMovimientos));
        sort($fechas);
        $anteriores = $corte === null ? [] : array_values(array_filter($fechas, static fn (string $f): bool => $f <= $corte));
        $posteriores = $corte === null ? $fechas : array_values(array_filter($fechas, static fn (string $f): bool => $f > $corte));
        $sobran = max(0, count($posteriores) - self::CANTIDAD);
        $agrupadas = [...$anteriores, ...array_slice($posteriores, 0, $sobran)];
        $visibles = array_slice($posteriores, $sobran);
        $hasta = $agrupadas === [] ? $corte : max([...$agrupadas, ...($corte === null ? [] : [$corte])]);

        return new self($visibles, $hasta, $corte !== null, $agrupadas !== []);
    }

    /** La fecha va en la columna "Hasta" (es anterior a las 6 visibles, o de la carga inicial). */
    public function esAgrupada(string $fecha): bool
    {
        return $this->hasta !== null && $fecha <= $this->hasta;
    }
}
