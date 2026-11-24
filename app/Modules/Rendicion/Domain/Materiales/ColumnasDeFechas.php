<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Materiales;

/**
 * Columnas de fecha de W-48 (RN-REN-50, resuelta DU-R06): las 6 fechas más recientes con movimientos, de la
 * más vieja a la más nueva; las anteriores se juntan en "Hasta {fecha}" (la más nueva de las agrupadas).
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
    ) {}

    /**
     * @param  list<string>  $fechasConMovimientos
     */
    public static function de(array $fechasConMovimientos): self
    {
        $fechas = array_values(array_unique($fechasConMovimientos));
        sort($fechas);

        if (count($fechas) <= self::CANTIDAD) {
            return new self($fechas, null);
        }

        $agrupadas = array_slice($fechas, 0, count($fechas) - self::CANTIDAD);

        return new self(array_slice($fechas, -self::CANTIDAD), $agrupadas[count($agrupadas) - 1]);
    }

    /** La fecha va en la columna "Hasta" (es anterior a las 6 visibles). */
    public function esAgrupada(string $fecha): bool
    {
        return $this->hasta !== null && $fecha <= $this->hasta;
    }
}
